<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Patch;
use Swag\PayPal\RestApi\V2\RequestUriV2;

class OrderResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

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
        bool $minimalResponse = true
    ): Order {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
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
        bool $minimalResponse = false
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
        bool $minimalResponse = false
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
}
