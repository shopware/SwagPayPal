<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V1\Api\Capture;
use Swag\PayPal\RestApi\V1\Api\DoVoid;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\RelatedResource\Order;
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class OrdersResource
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
            \sprintf('%s/%s', RequestUriV1::ORDERS_RESOURCE, $orderId)
        );

        return (new Order())->assign($response);
    }

    public function capture(string $orderId, Capture $capture, string $salesChannelId): Capture
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUriV1::ORDERS_RESOURCE, $orderId),
            $capture
        );

        $capture->assign($response);

        return $capture;
    }

    public function void(string $orderId, string $salesChannelId): DoVoid
    {
        $doVoid = new DoVoid();
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/do-void', RequestUriV1::ORDERS_RESOURCE, $orderId),
            $doVoid
        );

        $doVoid->assign($response);

        return $doVoid;
    }
}
