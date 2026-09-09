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
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Order as PayPalOrder;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class OrderExecuteService
{
    private OrderResource $orderResource;

    private OrderTransactionStateHandler $orderTransactionStateHandler;

    private OrderNumberPatchBuilder $orderNumberPatchBuilder;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        OrderResource $orderResource,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        OrderNumberPatchBuilder $orderNumberPatchBuilder,
        LoggerInterface $logger,
    ) {
        $this->orderResource = $orderResource;
        $this->orderTransactionStateHandler = $orderTransactionStateHandler;
        $this->orderNumberPatchBuilder = $orderNumberPatchBuilder;
        $this->logger = $logger;
    }

    /**
     * @throws PayPalApiException
     * @throws PayerActionRequiredException
     */
    public function captureOrAuthorizeOrder(
        string $transactionId,
        PayPalOrder $paypalOrder,
        string $salesChannelId,
        Context $context,
        string $partnerAttributionId,
    ): PayPalOrder {
        $this->logger->debug('Started');

        try {
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
        } catch (PayPalApiException $e) {
            // is a PayPalApiException itself, so it must not be wrapped twice
            if ($e instanceof PayerActionRequiredException
                || $e->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY
                || !$e->is(PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED)) {
                throw $e;
            }

            throw $this->createPayerActionRequiredException($transactionId, $paypalOrder, $e);
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

    private function doPayPalRequest(PayPalOrder $paypalOrder, string $salesChannelId, string $partnerAttributionId, string $transactionId, Context $context): PayPalOrder
    {
        if ($this->checkFinalizedStatus($paypalOrder, $salesChannelId, $transactionId, $context, false)) {
            return $paypalOrder;
        }

        // capturing an order PayPal already flagged is guaranteed to fail
        if ($paypalOrder->isset('status') && $paypalOrder->getStatus() === ConstantsV2::ORDER_PAYER_ACTION_REQUIRED) {
            throw $this->createPayerActionRequiredException($transactionId, $paypalOrder);
        }

        if ($paypalOrder->getIntent() === ConstantsV2::INTENT_CAPTURE) {
            $response = $this->orderResource->capture($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
        } else {
            $response = $this->orderResource->authorize($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
        }

        $this->checkFinalizedStatus($response, $salesChannelId, $transactionId, $context);

        return $response;
    }

    private function createPayerActionRequiredException(
        string $transactionId,
        PayPalOrder $paypalOrder,
        ?PayPalApiException $previous = null,
    ): PayerActionRequiredException {
        // PayPal returns the `payer-action` link on the failing response only, never on the order itself
        $error = $previous?->getPrevious();
        $exception = PayerActionRequiredException::payerActionRequired(
            $paypalOrder->getId(),
            $error instanceof ErrorApiException ? $error->getLinks() : null,
            $previous,
        );

        $this->logger->warning('PayPal requires another payer action before the order can be captured.', [
            'orderTransactionId' => $transactionId,
            'payPalOrderId' => $paypalOrder->getId(),
            'payerActionUrl' => $exception->getPayerActionUrl() ?? $this->resolvePayerActionUrl($paypalOrder),
            'error' => $previous,
        ]);

        return $exception;
    }

    private function resolvePayerActionUrl(PayPalOrder $paypalOrder): ?string
    {
        // Order::$links has no default and PayPal may omit it
        if (!$paypalOrder->isset('links')) {
            return null;
        }

        return $paypalOrder->getLinks()->getRelation(Link::RELATION_PAYER_ACTION)?->getHref();
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
