<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\OrderGateway;
use Shopware\PayPalSDK\Struct\V2\ConfirmOrder;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\Tracker;
use Shopware\PayPalSDK\Struct\V2\Patch;
use Shopware\PayPalSDK\Struct\V2\PatchCollection;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\LogKey;

#[Package('checkout')]
class OrderResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly OrderGateway $orderGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function get(string $orderId, string $salesChannelId): Order
    {
        return $this->orderGateway->getOrder($orderId, $this->apiContextFactory->getApiContext($salesChannelId));
    }

    /**
     * @throws PayPalApiException
     */
    public function create(
        Order $order,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = true,
        ?string $requestId = null,
        ?string $metaDataId = null,
    ): Order {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId)
            ->withPreferRepresentation(!$minimalResponse)
            ->withRequestId($requestId)
            ->withClientMetadataId($metaDataId);

        $response = $this->orderGateway->createOrder($order, $context);

        $this->logger->info('Created PayPal order {paypalOrderId}', [
            LogKey::CODE => LogCode::CHECKOUT_ORDER_CREATED,
            LogKey::SALES_CHANNEL_ID => $salesChannelId,
            LogKey::PARTNER_ATTRIBUTION_ID => $partnerAttributionId,
            // a minimal response only has ID and status
            LogKey::PAYPAL_ORDER => $order,
            LogKey::PAYPAL_ORDER_ID => $response->getId(),
        ]);

        return $response;
    }

    /**
     * @param Patch[] $patches
     *
     * @throws PayPalApiException
     */
    public function update(array $patches, string $orderId, string $salesChannelId, string $partnerAttributionId): void
    {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId);

        $this->orderGateway->patchOrder($orderId, new PatchCollection($patches), $context);
    }

    /**
     * Confirms the payment source of an existing PayPal order, which reopens its approval
     * with the experience context of the given payment source.
     *
     * @throws PayPalApiException
     */
    public function confirm(
        string $orderId,
        PaymentSource $paymentSource,
        string $salesChannelId,
        string $partnerAttributionId,
    ): Order {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId);

        $confirmOrder = new ConfirmOrder();
        $confirmOrder->setPaymentSource($paymentSource);

        return $this->orderGateway->confirmPaymentSource($orderId, $confirmOrder, $context);
    }

    /**
     * @throws PayPalApiException
     */
    public function capture(
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = false,
    ): Order {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId)
            ->withPreferRepresentation(!$minimalResponse);

        return $this->orderGateway->captureOrder($orderId, $context);
    }

    /**
     * @throws PayPalApiException
     */
    public function authorize(
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = false,
    ): Order {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId)
            ->withPreferRepresentation(!$minimalResponse);

        return $this->orderGateway->authorizeOrder($orderId, $context);
    }

    /**
     * @throws PayPalApiException
     */
    public function addTracker(
        Tracker $tracker,
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
    ): Order {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId);

        return $this->orderGateway->addTracker($tracker, $orderId, $context);
    }

    /**
     * @throws PayPalApiException
     */
    public function removeTracker(
        Tracker $tracker,
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
    ): void {
        $context = $this->apiContextFactory
            ->getApiContext($salesChannelId)
            ->withPartnerAttributionId($partnerAttributionId);

        $this->orderGateway->removeTracker($tracker, $orderId, $context);
    }
}
