<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\PayPal\Client;

use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\Client\TokenClient;
use SwagPayPal\PayPal\Client\TokenClientFactory;

class TokenClientFactoryMock extends TokenClientFactory
{
    public function createTokenClient(OAuthCredentials $credentials, string $url): TokenClient
    {
        return new TokenClientMock($credentials, $url);
    }
}
