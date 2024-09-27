<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\RequestUriV2;

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
            \sprintf('%s/%s', RequestUriV2::CAPTURES_RESOURCE, $captureId)
        );

        return (new Capture())->assign($response);
    }

    public function refund(
        string $captureId,
        Refund $refund,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = true,
    ): Refund {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            \sprintf('%s/%s/refund', RequestUriV2::CAPTURES_RESOURCE, $captureId),
            $refund,
            $headers
        );

        return $refund->assign($response);
    }
}
