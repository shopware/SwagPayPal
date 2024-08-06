<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\PartnerId;
use Swag\PayPal\RestApi\V1\Api\MerchantIntegrations;
use Swag\PayPal\RestApi\V1\RequestUriV1;
use Swag\PayPal\Setting\Exception\PayPalInvalidApiCredentialsException;

#[Package('checkout')]
class MerchantIntegrationsResource implements MerchantIntegrationsResourceInterface
{
    /**
     * @internal
     */
    public function __construct(private readonly PayPalClientFactoryInterface $payPalClientFactory)
    {
    }

    public function get(string $merchantId, ?string $salesChannelId = null, bool $sandboxActive = true): MerchantIntegrations
    {
        if (!$merchantId) {
            // throw new PayPalApiException(
            //     'merchant_id_missing',
            //     'The merchant id is missing',
            //     Response::HTTP_UNAUTHORIZED,
            //     PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS,
            // );
            /**
             * @deprecated tag:v10.0.0 - Will be replaced by a PayPalApiException
             */
            throw new PayPalInvalidApiCredentialsException();
        }

        $partnerId = $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE;

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId, isFirstParty: true)->sendGetRequest(
            \sprintf(RequestUriV1::MERCHANT_INTEGRATIONS_RESOURCE, $partnerId, $merchantId)
        );

        return (new MerchantIntegrations())->assign($response);
    }
}
