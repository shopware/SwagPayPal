<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\PartnerId;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\RequestUriV1;

class MerchantIntegrationsResource
{
    public const IdentityHeaderMerchantIDField = 'Caller_acct_num';

    private PayPalClientFactoryInterface $payPalClientFactory;

    public function __construct(
        PayPalClientFactoryInterface $payPalClientFactory
    ) {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function get(?string $salesChannelId = null, bool $sandboxActive = true): MerchantIntegrations
    {
        $merchantId = $this->getMerchantId($salesChannelId);

        $partnerId = $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE;

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf(RequestUriV1::MERCHANT_INTEGRATIONS_RESOURCE, $partnerId, $merchantId)
        );

        return (new MerchantIntegrations())->assign($response);
    }

    private function getMerchantId(?string $salesChannelId = null): string
    {
        $headers = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequestForHeaders(
            RequestUriV1::CLIENT_USERINFO_RESOURCE,
        );

        return $headers[self::IdentityHeaderMerchantIDField][0];
    }
}
