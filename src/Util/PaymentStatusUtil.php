<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Swag\PayPal\Payment\Builder\Util\PriceFormatter;
use Swag\PayPal\PayPal\Api\Capture;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Refund;
use Symfony\Component\HttpFoundation\Request;

class PaymentStatusUtil
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->priceFormatter = new PriceFormatter();
    }

    public function applyVoidStateToOrder(string $orderId, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);

        $this->orderTransactionStateHandler->cancel($transaction->getId(), $context);
    }

    /**
     * @deprecated tag:v2.0.0 - Deprecated since version 1.5.1 and will be removed in 2.0.0. Use "applyCaptureState" instead
     */
    public function applyCaptureStateToPayment(string $orderId, Request $request, Capture $captureResponse, Context $context): void
    {
        $this->applyCaptureState($orderId, $captureResponse, $context);
    }

    public function applyCaptureState(string $orderId, Capture $captureResponse, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $transactionId = $transaction->getId();
        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            throw new InvalidTransactionException($transactionId);
        }

        if ($captureResponse->isFinalCapture()) {
            $this->reopenTransaction($stateMachineState, $transactionId, $context);
            // If the previous state is "paid_partially", "paid" is currently not allowed as direct transition
            $this->orderTransactionStateHandler->process($transactionId, $context);
            $this->orderTransactionStateHandler->paid($transactionId, $context);

            return;
        }

        // TODO after NEXT-7683: Do transition even if transaction is already partially paid.
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
        Context $context
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
        $relatedResources = $paymentResponse->getTransactions()[0]->getRelatedResources();
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
        Context $context
    ): void {
        $refundStates = [OrderTransactionStates::STATE_PARTIALLY_REFUNDED, OrderTransactionStates::STATE_REFUNDED];
        if (\in_array($stateMachineState->getTechnicalName(), $refundStates, true)) {
            $this->orderTransactionStateHandler->reopen($transactionId, $context);
        }
    }

    private function setPartiallyRefundedState(
        ?StateMachineStateEntity $stateMachineState,
        string $transactionId,
        Context $context
    ): void {
        if ($stateMachineState === null) {
            throw new InvalidTransactionException($transactionId);
        }

        // TODO after NEXT-7683: Do transition even if transaction is already partially refunded.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_REFUNDED) {
            $this->orderTransactionStateHandler->refundPartially($transactionId, $context);
        }
    }

    /**
     * @throws OrderNotFoundException
     * @throws InvalidOrderException
     */
    private function getOrderTransaction(string $orderId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $criteria->getAssociation('transactions')->addSorting(new FieldSorting('createdAt'));
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        $transactionCollection = $order->getTransactions();

        if ($transactionCollection === null) {
            throw new InvalidOrderException($orderId);
        }

        $transaction = $transactionCollection->last();

        if ($transaction === null) {
            throw new InvalidOrderException($orderId);
        }

        return $transaction;
    }
}
