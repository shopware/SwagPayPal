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
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class CaptureResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function get(string $captureId, string $salesChannelId): Capture
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV1::CAPTURE_RESOURCE, $captureId)
        );

        return (new Capture())->assign($response);
    }
}
