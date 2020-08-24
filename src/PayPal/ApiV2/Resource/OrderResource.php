<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Resource;

use Swag\PayPal\PayPal\ApiV2\Api\Order;
use Swag\PayPal\PayPal\ApiV2\RequestUriV2;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;

class OrderResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function create(Order $order, string $salesChannelId, bool $minimalResponse = true): Order
    {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            RequestUriV2::ORDERS_RESOURCE,
            $order,
            $headers
        );

        return $order->assign($response);
    }

    public function get(string $orderId, string $salesChannelId): Order
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV2::ORDERS_RESOURCE, $orderId)
        );

        return (new Order())->assign($response);
    }

    public function capture(string $orderId, string $salesChannelId, bool $minimalResponse = true): Order
    {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUriV2::ORDERS_RESOURCE, $orderId),
            null,
            $headers
        );

        return (new Order())->assign($response);
    }

    public function authorize(string $orderId, string $salesChannelId, bool $minimalResponse = true): Order
    {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/authorize', RequestUriV2::ORDERS_RESOURCE, $orderId),
            null,
            $headers
        );

        return (new Order())->assign($response);
    }
}
