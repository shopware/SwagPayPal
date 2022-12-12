<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Response;

class OrderExecuteService
{
    private OrderResource $orderResource;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private LoggerInterface $logger;

    public function __construct(
        OrderResource $orderResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        LoggerInterface $logger
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
        string $partnerAttributionId
    ): PayPalOrder {
        $this->logger->debug('Started');

        try {
            return $this->doPayPalRequest($paypalOrder, $salesChannelId, $partnerAttributionId, $transactionId, $context);
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY
                || ($e->getIssue() !== PayPalApiException::ERROR_CODE_DUPLICATE_INVOICE_ID)) {
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
    }

    /**
     * @deprecated tag:v6.0.0 - will be removed, use captureOrAuthorizeOrder() instead
     *
     * @throws PayPalApiException
     */
    public function executeOrder(
        string $transactionId,
        string $paypalOrderId,
        string $salesChannelId,
        Context $context,
        string $partnerAttributionId
    ): PayPalOrder {
        $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelId);

        return $this->captureOrAuthorizeOrder($transactionId, $paypalOrder, $salesChannelId, $context, $partnerAttributionId);
    }

    private function doPayPalRequest(PayPalOrder $paypalOrder, string $salesChannelId, string $partnerAttributionId, string $transactionId, Context $context): PayPalOrder
    {
        if ($paypalOrder->getIntent() === PaymentIntentV2::CAPTURE) {
            $response = $this->orderResource->capture($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
            $captures = $this->getPayments($response, $salesChannelId)->getCaptures();
            if (empty($captures)) {
                throw new MissingPayloadException($response->getId(), 'purchaseUnit.payments.captures');
            }

            /** @var Capture $capture */
            $capture = \current($captures);
            if ($capture->getStatus() === PaymentStatusV2::ORDER_CAPTURE_COMPLETED) {
                $this->orderTransactionStateHandler->paid($transactionId, $context);
            }

            if ($capture->getStatus() === PaymentStatusV2::ORDER_CAPTURE_DECLINED
             || $capture->getStatus() === PaymentStatusV2::ORDER_CAPTURE_FAILED) {
                throw new OrderFailedException($paypalOrder->getId());
            }

            return $response;
        }

        $response = $this->orderResource->authorize($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
        $authorizations = $this->getPayments($response, $salesChannelId)->getAuthorizations();
        if (empty($authorizations)) {
            throw new MissingPayloadException($response->getId(), 'purchaseUnit.payments.authorizations');
        }

        /** @var Authorization $authorization */
        $authorization = \current($authorizations);
        if ($authorization->getStatus() === PaymentStatusV2::ORDER_AUTHORIZATION_CREATED) {
            $this->orderTransactionStateHandler->authorize($transactionId, $context);
        }

        if ($authorization->getStatus() === PaymentStatusV2::ORDER_AUTHORIZATION_DENIED
            || $authorization->getStatus() === PaymentStatusV2::ORDER_AUTHORIZATION_PARTIALLY_CREATED
            || $authorization->getStatus() === PaymentStatusV2::ORDER_AUTHORIZATION_VOIDED
            || $authorization->getStatus() === PaymentStatusV2::ORDER_AUTHORIZATION_EXPIRED) {
            throw new OrderFailedException($paypalOrder->getId());
        }

        return $response;
    }

    private function getPayments(PayPalOrder $order, string $salesChannelId): Payments
    {
        $payments = $this->getPaymentsFromOrder($order);
        if ($payments !== null) {
            return $payments;
        }

        $refetchedOrder = $this->orderResource->get($order->getId(), $salesChannelId);

        $payments = $this->getPaymentsFromOrder($refetchedOrder);
        if ($payments === null) {
            throw new MissingPayloadException($order->getId(), 'purchaseUnit.payments');
        }

        $order->setPurchaseUnits($refetchedOrder->getPurchaseUnits());

        return $payments;
    }

    private function getPaymentsFromOrder(PayPalOrder $order): ?Payments
    {
        $purchaseUnits = $order->getPurchaseUnits();
        if (empty($purchaseUnits)) {
            throw new MissingPayloadException($order->getId(), 'purchaseUnit');
        }

        return \current($purchaseUnits)->getPayments();
    }
}
