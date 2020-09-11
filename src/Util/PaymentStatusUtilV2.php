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
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;

class PaymentStatusUtilV2
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(
        EntityRepositoryInterface $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        PriceFormatter $priceFormatter
    ) {
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->priceFormatter = $priceFormatter;
    }

    public function applyRefundState(string $orderTransactionId, Refund $refundResponse, Order $payPalOrder, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);
        $transactionId = $transaction->getId();

        $refundAmount = $refundResponse->getSellerPayableBreakdown()->getTotalRefundedAmount()->getValue();
        $transactionAmount = $this->priceFormatter->formatPrice($transaction->getAmount()->getTotalPrice());

        if ($refundAmount === $transactionAmount) {
            $this->orderTransactionStateHandler->refund($transactionId, $context);

            return;
        }

        $capturedAmount = 0.0;
        $captures = $payPalOrder->getPurchaseUnits()[0]->getPayments()->getCaptures();
        if ($captures !== null) {
            foreach ($captures as $capture) {
                $capturedAmount += (float) $capture->getAmount()->getValue();
            }
        }

        if ($refundAmount === $this->priceFormatter->formatPrice($capturedAmount)) {
            $this->orderTransactionStateHandler->refund($transactionId, $context);

            return;
        }

        $this->setPartiallyRefundedState($transaction->getStateMachineState(), $transactionId, $context);
    }

    public function applyCaptureState(string $orderTransactionId, Capture $captureResponse, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);
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

        // TODO PPI-59 - Do transition even if transaction is already partially paid.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_PAID) {
            $this->reopenTransaction($stateMachineState, $transactionId, $context);
            $this->orderTransactionStateHandler->payPartially($transactionId, $context);
        }
    }

    public function applyVoidState(string $orderTransactionId, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderTransactionId, $context);

        $this->orderTransactionStateHandler->cancel($transaction->getId(), $context);
    }

    private function getOrderTransaction(string $orderTransactionId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderTransactionId]);
        /** @var OrderTransactionEntity|null $transaction */
        $transaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($transaction === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        return $transaction;
    }

    private function setPartiallyRefundedState(
        ?StateMachineStateEntity $stateMachineState,
        string $transactionId,
        Context $context
    ): void {
        if ($stateMachineState === null) {
            throw new InvalidTransactionException($transactionId);
        }

        // TODO PPI-59 - Do transition even if transaction is already partially refunded.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_REFUNDED) {
            $this->orderTransactionStateHandler->refundPartially($transactionId, $context);
        }
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
}
