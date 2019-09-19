<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\PayPal\Client;

use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\Client\TokenClient;
use Swag\PayPal\PayPal\Client\TokenClientFactory;

class TokenClientFactoryMock extends TokenClientFactory
{
    public function createTokenClient(OAuthCredentials $credentials, string $url): TokenClient
    {
        return new TokenClientMock($credentials, $url);
    }
}
