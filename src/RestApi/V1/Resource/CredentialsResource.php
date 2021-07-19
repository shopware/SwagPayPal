<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Swag\PayPal\RestApi\Client\CredentialsClientFactory;
use Swag\PayPal\RestApi\Client\TokenClientFactory;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;

class CredentialsResource
{
    private TokenClientFactory $tokenClientFactory;

    private CredentialsClientFactory $credentialsClientFactory;

    private TokenValidator $tokenValidator;

    public function __construct(
        TokenClientFactory $tokenClientFactory,
        CredentialsClientFactory $credentialsClientFactory,
        TokenValidator $tokenValidator
    ) {
        $this->tokenClientFactory = $tokenClientFactory;
        $this->credentialsClientFactory = $credentialsClientFactory;
        $this->tokenValidator = $tokenValidator;
    }

    public function getClientCredentials(
        string $authCode,
        string $sharedId,
        string $nonce,
        string $url,
        string $partnerId
    ): array {
        $credentialsClient = $this->credentialsClientFactory->createCredentialsClient($url);
        $accessToken = $credentialsClient->getAccessToken($authCode, $sharedId, $nonce);

        return $credentialsClient->getCredentials($accessToken, $partnerId);
    }

    public function testApiCredentials(OAuthCredentials $credentials, string $url): bool
    {
        $tokenClient = $this->tokenClientFactory->createTokenClient($credentials, $url);

        $token = new Token();
        $token->assign($tokenClient->getToken());

        return $this->tokenValidator->isTokenValid($token);
    }
}
