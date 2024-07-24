<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerId;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\Setting\Exception\PayPalInvalidApiCredentialsException;

#[Package('checkout')]
class ApiCredentialService implements ApiCredentialServiceInterface
{
    private CredentialsResource $credentialsResource;

    /**
     * @internal
     */
    public function __construct(CredentialsResource $credentialsResource)
    {
        $this->credentialsResource = $credentialsResource;
    }

    /**
     * @throws PayPalInvalidApiCredentialsException
     */
    public function testApiCredentials(string $clientId, string $clientSecret, bool $sandboxActive): bool
    {
        $credentials = new OAuthCredentials();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);
        $credentials->setUrl($sandboxActive ? BaseURL::SANDBOX : BaseURL::LIVE);

        try {
            return $this->credentialsResource->testApiCredentials($credentials);
        } catch (PayPalApiException $payPalApiException) {
            /**
             * @deprecated tag:v10.0.0 - Will be removed, use the exception directly
             */
            if ($payPalApiException->is(PayPalApiException::ERROR_CODE_INVALID_CREDENTIALS)) {
                throw new PayPalInvalidApiCredentialsException();
            }

            throw $payPalApiException;
        }
    }

    public function getApiCredentials(string $authCode, string $sharedId, string $nonce, bool $sandboxActive): array
    {
        $url = $sandboxActive ? BaseURL::SANDBOX : BaseURL::LIVE;
        $partnerId = $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE;

        return $this->credentialsResource->getClientCredentials($authCode, $sharedId, $nonce, $url, $partnerId);
    }
}
