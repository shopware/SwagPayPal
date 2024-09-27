<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\Tracker;
use Swag\PayPal\RestApi\V2\Api\Patch;
use Swag\PayPal\RestApi\V2\RequestUriV2;

#[Package('checkout')]
class OrderResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function get(string $orderId, string $salesChannelId): Order
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV2::ORDERS_RESOURCE, $orderId)
        );

        return (new Order())->assign($response);
    }

    public function create(
        Order $order,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = true,
        ?string $requestId = null,
        ?string $metaDataId = null,
    ): Order {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }
        if ($metaDataId !== null) {
            $headers['PayPal-Client-Metadata-Id'] = $metaDataId;
        }
        if ($requestId !== null) {
            $headers['PayPal-Request-Id'] = $requestId;
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            RequestUriV2::ORDERS_RESOURCE,
            $order,
            $headers
        );

        return $order->assign($response);
    }

    /**
     * @param Patch[] $patches
     */
    public function update(array $patches, string $orderId, string $salesChannelId, string $partnerAttributionId): void
    {
        $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPatchRequest(
            \sprintf('%s/%s', RequestUriV2::ORDERS_RESOURCE, $orderId),
            $patches
        );
    }

    public function capture(
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = false,
    ): Order {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUriV2::ORDERS_RESOURCE, $orderId),
            null,
            $headers
        );

        return (new Order())->assign($response);
    }

    public function authorize(
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = false,
    ): Order {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            \sprintf('%s/%s/authorize', RequestUriV2::ORDERS_RESOURCE, $orderId),
            null,
            $headers
        );

        return (new Order())->assign($response);
    }

    public function addTracker(
        Tracker $tracker,
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
    ): Order {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            \sprintf('%s/%s/track', RequestUriV2::ORDERS_RESOURCE, $orderId),
            $tracker,
        );

        return (new Order())->assign($response);
    }

    public function removeTracker(
        Tracker $tracker,
        string $orderId,
        string $salesChannelId,
        string $partnerAttributionId,
    ): void {
        $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPatchRequest(
            \sprintf(
                '%s/%s/trackers/%s-%s',
                RequestUriV2::ORDERS_RESOURCE,
                $orderId,
                $tracker->getCaptureId(),
                $tracker->getTrackingNumber(),
            ),
            [
                (new Patch())->assign([
                    'op' => Patch::OPERATION_REPLACE,
                    'path' => '/status',
                    'value' => Order\PurchaseUnit\Shipping\Tracker::STATUS_CANCELLED,
                ]),
            ],
        );
    }
}
