<?php declare(strict_types=1);

namespace Swag\PayPal\Util;

use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Exception\IllegalTransitionException;
use Shopware\Core\System\StateMachine\Exception\StateMachineNotFoundException;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Payment\PayPalPaymentController;
use Symfony\Component\HttpFoundation\Request;

class PaymentStatusUtil
{
    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;

    /**
     * @var OrderTransactionStateHandler
     */
    private $orderTransactionStateHandler;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
    }

    /**
     * @throws IllegalTransitionException
     * @throws InconsistentCriteriaIdsException
     * @throws OrderNotFoundException
     * @throws StateMachineNotFoundException
     * @throws InvalidOrderException
     * @throws InvalidTransactionException
     */
    public function applyVoidStateToOrder(string $orderId, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);

        $this->orderTransactionStateHandler->cancel($transaction->getId(), $context);
    }

    /**
     * @throws IllegalTransitionException
     * @throws InconsistentCriteriaIdsException
     * @throws OrderNotFoundException
     * @throws StateMachineNotFoundException
     * @throws InvalidOrderException
     * @throws InvalidTransactionException
     */
    public function applyCaptureStateToPayment(string $orderId, Request $request, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $amountToCapture = round((float) $request->request->get(PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_AMOUNT), 2);
        $isFinalCapture = $request->request->getBoolean(PayPalPaymentController::REQUEST_PARAMETER_CAPTURE_IS_FINAL);

        if ($isFinalCapture || $amountToCapture === $transaction->getAmount()->getTotalPrice()) {
            $this->orderTransactionStateHandler->pay($transaction->getId(), $context);

            return;
        }

        $this->orderTransactionStateHandler->payPartially($transaction->getId(), $context);
    }

    /**
     * @throws IllegalTransitionException
     * @throws InconsistentCriteriaIdsException
     * @throws OrderNotFoundException
     * @throws StateMachineNotFoundException
     * @throws InvalidOrderException
     * @throws InvalidTransactionException
     */
    public function applyRefundStateToPayment(string $orderId, Request $request, Context $context): void
    {
        $transaction = $this->getOrderTransaction($orderId, $context);
        $refundAmount = round((float) $request->request->get(PayPalPaymentController::REQUEST_PARAMETER_REFUND_AMOUNT), 2);

        if ($refundAmount === $transaction->getAmount()->getTotalPrice()) {
            $this->orderTransactionStateHandler->refund($transaction->getId(), $context);

            return;
        }

        $this->orderTransactionStateHandler->refundPartially($transaction->getId(), $context);
    }

    /**
     * @throws InconsistentCriteriaIdsException
     * @throws OrderNotFoundException
     * @throws InvalidOrderException
     */
    private function getOrderTransaction(string $orderId, Context $context): OrderTransactionEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
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
