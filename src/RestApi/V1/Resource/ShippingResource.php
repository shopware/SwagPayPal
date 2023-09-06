<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V1\Api\Shipping;
use Swag\PayPal\RestApi\V1\Api\Shipping\Tracker;
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class ShippingResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
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

    public function update(Tracker $tracker, string $salesChannelId): void
    {
        $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPutRequest(
            \sprintf('%s/trackers/%s-%s', RequestUriV1::SHIPPING_RESOURCE, $tracker->getTransactionId(), $tracker->getTrackingNumber()),
            $tracker
        );
    }
}
