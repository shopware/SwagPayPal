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
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
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
    public function executeOrder(
        string $transactionId,
        string $paypalOrderId,
        string $salesChannelId,
        Context $context,
        string $partnerAttributionId
    ): PayPalOrder {
        $this->logger->debug('Started');

        $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelId);

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
                $paypalOrderId,
                $salesChannelId,
                $partnerAttributionId
            );

            return $this->doPayPalRequest($paypalOrder, $salesChannelId, $partnerAttributionId, $transactionId, $context);
        }
    }

    private function doPayPalRequest(PayPalOrder $paypalOrder, string $salesChannelId, string $partnerAttributionId, string $transactionId, Context $context): PayPalOrder
    {
        if ($paypalOrder->getIntent() === PaymentIntentV2::CAPTURE) {
            $response = $this->orderResource->capture($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
            if ($response->getStatus() === PaymentStatusV2::ORDER_COMPLETED) {
                $this->orderTransactionStateHandler->paid($transactionId, $context);
            }
        } else {
            $response = $this->orderResource->authorize($paypalOrder->getId(), $salesChannelId, $partnerAttributionId);
            if ($response->getStatus() === PaymentStatusV2::ORDER_COMPLETED) {
                $this->orderTransactionStateHandler->authorize($transactionId, $context);
            }
        }

        return $response;
    }
}
