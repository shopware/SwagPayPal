<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Client;

use Swag\PayPal\PayPal\Api\OAuthCredentials;

class TokenClientFactory
{
    public function createTokenClient(OAuthCredentials $credentials, string $url): TokenClient
    {
        return new TokenClient($credentials, $url);
    }
}
