<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Swag\PayPal\RestApi\V1\Api\Capture;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Refund;

#[Package('checkout')]
class PaymentStatusUtil
{
    private EntityRepository $orderRepository;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private PriceFormatter $priceFormatter;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        PriceFormatter $priceFormatter,
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->priceFormatter = $priceFormatter;
    }

    public function applyVoidStateToOrder(string $orderId, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);

        $this->orderTransactionStateHandler->cancel($transaction->getId(), $context);
    }

    public function applyCaptureState(string $orderId, Capture $captureResponse, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $transactionId = $transaction->getId();
        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            throw PaymentException::invalidTransaction($transactionId);
        }

        if ($captureResponse->isIsFinalCapture()) {
            $this->reopenTransaction($stateMachineState, $transactionId, $context);
            // If the previous state is "paid_partially", "paid" is currently not allowed as direct transition
            if ($stateMachineState->getTechnicalName() === OrderTransactionStates::STATE_PARTIALLY_PAID) {
                $this->orderTransactionStateHandler->process($transactionId, $context);
            }
            $this->orderTransactionStateHandler->paid($transactionId, $context);

            return;
        }

        // TODO PPI-59 - Do transition even if transaction is already partially paid.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_PAID) {
            $this->reopenTransaction($stateMachineState, $transactionId, $context);
            $this->orderTransactionStateHandler->payPartially($transactionId, $context);
        }
    }

    public function applyRefundStateToPayment(string $orderId, Refund $refundResponse, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $transactionId = $transaction->getId();

        $refundAmount = $this->priceFormatter->roundPrice(
            (float) $refundResponse->getTotalRefundedAmount()->getValue()
        );

        if ($refundAmount === $transaction->getAmount()->getTotalPrice()) {
            $this->orderTransactionStateHandler->refund($transactionId, $context);

            return;
        }

        $this->setPartiallyRefundedState($transaction->getStateMachineState(), $transactionId, $context);
    }

    public function applyRefundStateToCapture(
        string $orderId,
        Refund $refundResponse,
        Payment $paymentResponse,
        Context $context,
    ): void {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $transactionId = $transaction->getId();

        $refundAmount = $this->priceFormatter->roundPrice(
            (float) $refundResponse->getTotalRefundedAmount()->getValue()
        );

        if ($refundAmount === $transaction->getAmount()->getTotalPrice()) {
            $this->orderTransactionStateHandler->refund($transactionId, $context);

            return;
        }

        $capturedAmount = 0.0;
        $paymentTransaction = $paymentResponse->getTransactions()->first();
        if ($paymentTransaction === null) {
            return;
        }
        $relatedResources = $paymentTransaction->getRelatedResources();
        foreach ($relatedResources as $relatedResource) {
            $capture = $relatedResource->getCapture();
            if ($capture === null) {
                continue;
            }

            $capturedAmount += (float) $capture->getAmount()->getTotal();
        }

        if ($refundAmount === $capturedAmount) {
            $this->orderTransactionStateHandler->refund($transactionId, $context);

            return;
        }

        $this->setPartiallyRefundedState($transaction->getStateMachineState(), $transactionId, $context);
    }

    private function reopenTransaction(
        StateMachineStateEntity $stateMachineState,
        string $transactionId,
        Context $context,
    ): void {
        $refundStates = [OrderTransactionStates::STATE_PARTIALLY_REFUNDED, OrderTransactionStates::STATE_REFUNDED];
        if (\in_array($stateMachineState->getTechnicalName(), $refundStates, true)) {
            $this->orderTransactionStateHandler->reopen($transactionId, $context);
        }
    }

    private function setPartiallyRefundedState(
        ?StateMachineStateEntity $stateMachineState,
        string $transactionId,
        Context $context,
    ): void {
        if ($stateMachineState === null) {
            throw PaymentException::invalidTransaction($transactionId);
        }

        // TODO PPI-59 - Do transition even if transaction is already partially refunded.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_REFUNDED) {
            $this->orderTransactionStateHandler->refundPartially($transactionId, $context);
        }
    }

    /**
     * @throws ShopwareHttpException
     */
    private function getOrderTransaction(string $orderId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.stateMachineState');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        if ($order === null) {
            throw OrderException::orderNotFound($orderId);
        }

        $transactionCollection = $order->getTransactions();

        if ($transactionCollection === null) {
            throw PaymentException::invalidOrder($orderId);
        }

        $transaction = $transactionCollection->last();

        if ($transaction === null) {
            throw PaymentException::invalidOrder($orderId);
        }

        return $transaction;
    }
}
