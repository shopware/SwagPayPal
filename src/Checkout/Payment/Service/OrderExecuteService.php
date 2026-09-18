<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order as PayPalOrder;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\ApplePay;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Card;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\GooglePay;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Paypal;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Venmo;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Lock\LockFactory;

#[Package('checkout')]
class OrderExecuteService
{
    private OrderResource $orderResource;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private LoggerInterface $logger;

    /**
     * @internal
     *
     * @param EntityRepository<OrderTransactionCollection> $orderTransactionRepository
     */
    public function __construct(
        OrderResource $orderResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        LoggerInterface $logger,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly LockFactory $lockFactory,
    ) {
        $this->orderResource = $orderResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->logger = $logger;
    }

    /**
     * @throws PayPalApiException
     */
    public function captureOrAuthorizeOrder(
        string $transactionId,
        PayPalOrder $paypalOrder,
        string $salesChannelId,
        Context $context,
        string $partnerAttributionId,
    ): PayPalOrder {
        $this->logger->debug('Started');

        $lock = $this->lockFactory->createLock('swag-paypal-order-' . $paypalOrder->getId());
        if (!$lock->acquire(true)) {
            return $paypalOrder;
        }

        try {
            $transaction = $this->orderTransactionRepository->search(new Criteria([$transactionId]), $context)->getEntities()->first();
            if ($transaction?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED) === $paypalOrder->getId()) {
                $this->checkFinalizedStatus($paypalOrder, $salesChannelId, $transactionId, $context, false);

                return $paypalOrder;
            }

            try {
                return $this->doPayPalRequest($paypalOrder, $salesChannelId, $partnerAttributionId, $transactionId, $context);
            } catch (PayPalApiException $e) {
                if ($e->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY
                    || !$e->is(PayPalApiException::ISSUE_DUPLICATE_INVOICE_ID)) {
                    throw $e;
                }

                $this->logger->warning('Duplicate order number detected. Retrying payment without order number.');

                $this->orderResource->update(
                    [$this->orderNumberPatchBuilder->createRemoveOrderNumberPatch()],
                    $paypalOrder->getId(),
                    $salesChannelId,
                    $partnerAttributionId
                );

                return $this->doPayPalRequest($paypalOrder, $salesChannelId, $partnerAttributionId, $transactionId, $context);
            }
        } finally {
            $lock->release();
        }
    }

    public function isCancellationAllowed(string $paypalOrderId, string $salesChannelId, string $transactionId, Context $context): bool
    {
        $lock = $this->lockFactory->createLock('swag-paypal-order-' . $paypalOrderId);
        if (!$lock->acquire()) {
            return false;
        }

        try {
            $criteria = (new Criteria([$transactionId]))->addAssociations(['stateMachineState', 'paymentMethod']);
            $transaction = $this->orderTransactionRepository->search($criteria, $context)->getEntities()->first();
            if ($transaction === null
                || $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID) !== $paypalOrderId
                || $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED) !== $paypalOrderId
                || $transaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED) === $paypalOrderId
                || !\in_array($transaction->getStateMachineState()?->getTechnicalName(), [
                    OrderTransactionStates::STATE_OPEN,
                    OrderTransactionStates::STATE_UNCONFIRMED,
                    OrderTransactionStates::STATE_IN_PROGRESS,
                ], true)) {
                return false;
            }

            try {
                $order = $this->orderResource->get($paypalOrderId, $salesChannelId);
            } catch (\Throwable $e) {
                $this->logger->warning('Could not verify PayPal order before cancellation. Preserving transaction state for retry.', [
                    'paypalOrderId' => $paypalOrderId,
                    'salesChannelId' => $salesChannelId,
                    'exception' => $e,
                ]);

                return false;
            }

            return $this->isUnexecutedOrder($order, $paypalOrderId, $transaction->getPaymentMethod()?->getHandlerIdentifier());
        } finally {
            $lock->release();
        }
    }

    public function checkFinalizedStatus(PayPalOrder $order, string $salesChannelId, string $transactionId, Context $context, bool $refetch = true): bool
    {
        if ($order->getIntent() === ConstantsV2::INTENT_CAPTURE) {
            $capture = $this->getPayments($order, $salesChannelId, $refetch)?->getCaptures()?->first();
            if ($capture === null) {
                return false;
            }

            if ($capture->getStatus() === ConstantsV2::ORDER_CAPTURE_COMPLETED) {
                $this->orderTransactionStateHandler->paid($transactionId, $context);

                return true;
            }

            if ($capture->getStatus() === ConstantsV2::ORDER_CAPTURE_DECLINED
                || $capture->getStatus() === ConstantsV2::ORDER_CAPTURE_FAILED) {
                throw new OrderFailedException($order->getId());
            }

            return false;
        }

        $authorization = $this->getPayments($order, $salesChannelId, $refetch)?->getAuthorizations()?->first();
        if ($authorization === null) {
            return false;
        }

        if ($authorization->getStatus() === ConstantsV2::ORDER_AUTHORIZATION_CREATED) {
            $this->orderTransactionStateHandler->authorize($transactionId, $context);

            return true;
        }

        if ($authorization->getStatus() === ConstantsV2::ORDER_AUTHORIZATION_DENIED
            || $authorization->getStatus() === ConstantsV2::ORDER_AUTHORIZATION_VOIDED) {
            throw new OrderFailedException($order->getId());
        }

        return false;
    }

    private function isUnexecutedOrder(PayPalOrder $order, string $paypalOrderId, ?string $paymentHandler): bool
    {
        if (!$order->isset('id') || $order->getId() !== $paypalOrderId || !$order->isset('status')) {
            return false;
        }

        $allowedStatuses = [ConstantsV2::ORDER_CREATED, ConstantsV2::ORDER_PAYER_ACTION_REQUIRED];
        if ($this->isManuallyExecutedOrder($order, $paymentHandler)) {
            $allowedStatuses[] = ConstantsV2::ORDER_APPROVED;
        }

        if (!\in_array($order->getStatus(), $allowedStatuses, true)) {
            return false;
        }

        if ($order->getPurchaseUnits()->first() === null) {
            return false;
        }

        foreach ($order->getPurchaseUnits() as $purchaseUnit) {
            $payments = $purchaseUnit->getPayments();
            if ($payments?->getCaptures()?->first() !== null
                || $payments?->getAuthorizations()?->first() !== null
                || $payments?->getRefunds()?->first() !== null) {
                return false;
            }
        }

        return true;
    }

    private function isManuallyExecutedOrder(PayPalOrder $order, ?string $paymentHandler): bool
    {
        if ($order->isset('processingInstruction')) {
            return $order->getProcessingInstruction() === 'NO_INSTRUCTION';
        }

        $source = $order->getPaymentSource()?->first();

        // Older wallet responses expose only payer data, without a payment_source.
        if ($order->getPaymentSource() === null && $paymentHandler === PayPalPaymentHandler::class) {
            return true;
        }

        return $source instanceof Paypal || $source instanceof Card || $source instanceof ApplePay || $source instanceof GooglePay || $source instanceof Venmo;
    }

    private function doPayPalRequest(PayPalOrder $paypalOrder, string $salesChannelId, string $partnerAttributionId, string $transactionId, Context $context): PayPalOrder
    {
        if ($this->checkFinalizedStatus($paypalOrder, $salesChannelId, $transactionId, $context, false)) {
            return $paypalOrder;
        }

        // A timeout must not make an already submitted payment look safe to cancel.
        $this->orderTransactionRepository->update([[
            'id' => $transactionId,
            'customFields' => [SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED => $paypalOrder->getId()],
        ]], $context);

        if ($paypalOrder->getIntent() === ConstantsV2::INTENT_CAPTURE) {
            $response = $this->orderResource->capture($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
        } else {
            $response = $this->orderResource->authorize($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
        }

        $this->checkFinalizedStatus($response, $salesChannelId, $transactionId, $context);

        return $response;
    }

    private function getPayments(PayPalOrder $order, string $salesChannelId, bool $refetch): ?Payments
    {
        $payments = $order->getPurchaseUnits()->first()?->getPayments();
        if ($payments !== null) {
            return $payments;
        }

        if (!$refetch) {
            return null;
        }

        $refetchedOrder = $this->orderResource->get($order->getId(), $salesChannelId);

        $payments = $refetchedOrder->getPurchaseUnits()->first()?->getPayments();
        if ($payments === null) {
            return null;
        }

        $order->setPurchaseUnits($refetchedOrder->getPurchaseUnits());

        return $payments;
    }
}
