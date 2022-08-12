<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V1\Api\Shipping;
use Swag\PayPal\RestApi\V1\RequestUriV1;

class ShippingResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function batch(Shipping $shippingBatch, string $salesChannelId): void
    {
        $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/trackers-batch', RequestUriV1::SHIPPING_RESOURCE),
            $shippingBatch
        );
    }
}
