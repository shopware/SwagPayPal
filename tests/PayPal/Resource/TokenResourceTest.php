<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\Resource;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Test\Mock\CacheItemWithTokenMock;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\CacheWithTokenMock;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientMock;

class TokenResourceTest extends TestCase
{
    public function testGetToken(): void
    {
        $token = $this->getTokenResource(false)->getToken(new OAuthCredentials(), 'url');

        $dateNow = new \DateTime('now');

        static::assertSame(TokenClientMock::ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(TokenClientMock::TOKEN_TYPE, $token->getTokenType());
        static::assertInstanceOf(\DateTime::class, $token->getExpireDateTime());
        static::assertTrue($dateNow < $token->getExpireDateTime());
    }

    public function testTestApiCredentials(): void
    {
        $result = $this->getTokenResource()->testApiCredentials(new OAuthCredentials(), 'url');

        static::assertTrue($result);
    }

    public function testGetTokenFromCache(): void
    {
        $token = $this->getTokenResource()->getToken(new OAuthCredentials(), 'url');

        static::assertSame(CacheItemWithTokenMock::ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(TokenClientMock::TOKEN_TYPE, $token->getTokenType());
        static::assertInstanceOf(\DateTime::class, $token->getExpireDateTime());
    }

    private function getTokenResource(bool $withToken = true): TokenResource
    {
        $cacheItemPool = new CacheWithTokenMock();
        if ($withToken === false) {
            $cacheItemPool = new CacheMock();
        }

        $logger = new Logger('testLogger');

        return new TokenResource(
            $cacheItemPool,
            new TokenClientFactoryMock($logger),
            new CredentialsClientFactoryMock($logger)
        );
    }
}
