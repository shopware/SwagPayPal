<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\OrderGateway;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\Tracker;
use Shopware\PayPalSDK\Struct\V2\Patch;
use Shopware\PayPalSDK\Struct\V2\PatchCollection;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

#[Package('checkout')]
class OrderResource
{
    /**
     * @internal
     */
    public function __construct(
        private readonly OrderGateway $orderGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
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

        return $this->orderGateway->createOrder($order, $context);
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
