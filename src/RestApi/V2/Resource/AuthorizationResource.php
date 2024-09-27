<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\RequestUriV2;

#[Package('checkout')]
class AuthorizationResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function get(string $authorizationId, string $salesChannelId): Authorization
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV2::AUTHORIZATIONS_RESOURCE, $authorizationId)
        );

        return (new Authorization())->assign($response);
    }

    public function capture(
        string $authorizationId,
        Capture $capture,
        string $salesChannelId,
        string $partnerAttributionId,
        bool $minimalResponse = true,
    ): Capture {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUriV2::AUTHORIZATIONS_RESOURCE, $authorizationId),
            $capture,
            $headers
        );

        return $capture->assign($response);
    }

    public function void(string $authorizationId, string $salesChannelId, string $partnerAttributionId): void
    {
        $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId)->sendPostRequest(
            \sprintf('%s/%s/void', RequestUriV2::AUTHORIZATIONS_RESOURCE, $authorizationId),
            null
        );
    }
}
