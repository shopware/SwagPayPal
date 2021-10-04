<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Test\Mock\CacheItemWithTokenMock;
use Swag\PayPal\Test\Mock\CacheMock;
use Swag\PayPal\Test\Mock\CacheWithTokenMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;

class TokenResourceTest extends TestCase
{
    public function testGetToken(): void
    {
        $token = $this->getTokenResource(false)->getToken($this->getCredentials());

        $dateNow = new \DateTime('now');

        static::assertSame(CreateTokenResponseFixture::ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(CreateTokenResponseFixture::TOKEN_TYPE, $token->getTokenType());
        static::assertTrue($dateNow < $token->getExpireDateTime());
    }

    public function testGetTokenFromCache(): void
    {
        $token = $this->getTokenResource()->getToken($this->getCredentials());

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
            new TokenValidator()
        );
    }

    private function getCredentials(): OAuthCredentials
    {
        $credentials = new OAuthCredentials();
        $credentials->setRestId('restId');
        $credentials->setRestSecret('restSecret');
        $credentials->setUrl(BaseURL::SANDBOX);

        return $credentials;
    }
}
