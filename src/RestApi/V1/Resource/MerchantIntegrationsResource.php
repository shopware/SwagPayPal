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
use Swag\PayPal\Setting\Exception\PayPalInvalidApiCredentialsException;

class MerchantIntegrationsResource implements MerchantIntegrationsResourceInterface
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    public function __construct(
        PayPalClientFactoryInterface $payPalClientFactory
    ) {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function get(string $merchantId, ?string $salesChannelId = null, bool $sandboxActive = true): MerchantIntegrations
    {
        if (!$merchantId) {
            throw new PayPalInvalidApiCredentialsException();
        }

        $partnerId = $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE;

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf(RequestUriV1::MERCHANT_INTEGRATIONS_RESOURCE, $partnerId, $merchantId)
        );

        return (new MerchantIntegrations())->assign($response);
    }
}
