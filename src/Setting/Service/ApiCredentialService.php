<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\Client\PayPalClient;
use Swag\PayPal\RestApi\Client\TokenClientFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\PartnerId;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\RequestUriV1;
use Swag\PayPal\RestApi\V1\Resource\CredentialsResource;
use Swag\PayPal\RestApi\V1\Service\CredentialProviderInterface;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;

#[Package('checkout')]
class ApiCredentialService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly CredentialsResource $credentialsResource,
        private readonly TokenClientFactoryInterface $tokenClientFactory,
        private readonly TokenValidator $tokenValidator,
        private readonly CredentialProviderInterface $credentialProvider,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws PayPalApiException
     */
    public function testApiCredentials(string $clientId, string $clientSecret, bool $sandboxActive, ?string $merchantPayerId): bool
    {
        $credentials = new OAuthCredentials();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);
        $credentials->setUrl($sandboxActive ? BaseURL::SANDBOX : BaseURL::LIVE);

        $tokenClient = $this->tokenClientFactory->createTokenClient($credentials);
        $token = new Token();
        $token->assign($tokenClient->getToken());

        if (!$this->tokenValidator->isTokenValid($token)) {
            return false;
        }

        if ($merchantPayerId === null) {
            return true;
        }

        $client = new PayPalClient(
            $this->credentialProvider->createAuthorizationHeaders($token, null),
            $credentials->getUrl(),
            $this->logger,
            PartnerAttributionId::PAYPAL_PPCP
        );

        $client->sendGetRequest(
            \sprintf(RequestUriV1::MERCHANT_INTEGRATIONS_RESOURCE, $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE, $merchantPayerId)
        );

        return true;
    }

    public function getApiCredentials(string $authCode, string $sharedId, string $nonce, bool $sandboxActive): array
    {
        $url = $sandboxActive ? BaseURL::SANDBOX : BaseURL::LIVE;
        $partnerId = $sandboxActive ? PartnerId::SANDBOX : PartnerId::LIVE;

        return $this->credentialsResource->getClientCredentials($authCode, $sharedId, $nonce, $url, $partnerId);
    }
}
