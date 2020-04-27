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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Swag\PayPal\Payment\Builder\Util\PriceFormatter;
use Swag\PayPal\Payment\PayPalPaymentController;
use Swag\PayPal\PayPal\Api\Capture;
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

    public function applyCaptureStateToPayment(string $orderId, Request $request, Capture $captureResponse, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $isFinalCapture = $captureResponse->isFinalCapture()
            || $request->request->getBoolean(PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_IS_FINAL);

        if ($isFinalCapture) {
            $this->orderTransactionStateHandler->paid($transaction->getId(), $context);

            return;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return;
        }

        // TODO after NEXT-7683: Do transition even if transaction is already partially paid.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_PAID) {
            $this->orderTransactionStateHandler->payPartially($transaction->getId(), $context);
        }
    }

    public function applyRefundStateToPayment(string $orderId, Refund $refundResponse, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);

        // unfortunately, this might only be for one capture, so we may not get the actual total refunded amount
        $refundAmount = $this->priceFormatter->roundPrice((float) $refundResponse->getTotalRefundedAmount()->getValue());

        if ($refundAmount === $transaction->getAmount()->getTotalPrice()) {
            $this->orderTransactionStateHandler->refund($transaction->getId(), $context);

            return;
        }

        $stateMachineState = $transaction->getStateMachineState();
        if ($stateMachineState === null) {
            return;
        }

        // TODO after NEXT-7683: Do transition even if transaction is already partially refunded.
        if ($stateMachineState->getTechnicalName() !== OrderTransactionStates::STATE_PARTIALLY_REFUNDED) {
            $this->orderTransactionStateHandler->refundPartially($transaction->getId(), $context);
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
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        if ($order === null) {
            throw new OrderNotFoundException($orderId);
        }

        $transactionCollection = $order->getTransactions();

        if ($transactionCollection === null) {
            throw new InvalidOrderException($orderId);
        }

        $transaction = $transactionCollection->first();

        if ($transaction === null) {
            throw new InvalidOrderException($orderId);
        }

        return $transaction;
    }
}
