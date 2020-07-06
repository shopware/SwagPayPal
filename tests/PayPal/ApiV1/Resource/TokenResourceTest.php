<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\ApiV1\Resource;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\PayPal\ApiV1\Api\OAuthCredentials;
use Swag\PayPal\PayPal\ApiV1\Resource\TokenResource;
use Swag\PayPal\Test\Mock\CacheItemWithTokenMock;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\CacheWithTokenMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\CredentialsClientFactoryMock;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;

class TokenResourceTest extends TestCase
{
    public function testGetToken(): void
    {
        $token = $this->getTokenResource(false)->getToken(new OAuthCredentials(), 'url');

        $dateNow = new \DateTime('now');

        static::assertSame(CreateTokenResponseFixture::ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(CreateTokenResponseFixture::TOKEN_TYPE, $token->getTokenType());
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
        static::assertSame(CreateTokenResponseFixture::TOKEN_TYPE, $token->getTokenType());
    }

    private function getTokenResource(bool $withToken = true): TokenResource
    {
        $cacheItemPool = new CacheWithTokenMock();
        if ($withToken === false) {
            $cacheItemPool = new CacheMock();
        }

        $logger = new LoggerMock();

        return new TokenResource(
            $cacheItemPool,
            new TokenClientFactoryMock($logger),
            new CredentialsClientFactoryMock($logger)
        );
    }
}
