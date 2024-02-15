<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\BaseURL;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\RestApi\V1\Service\CredentialProviderInterface;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateTokenResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class TokenResourceTest extends TestCase
{
    private const CACHED_ACCESS_TOKEN = 'A21AAEaQMaSheELTFsynkQLwXBZIr-fObE9PtGjr6_SOVEBXWNaJu1DvwKfLiJdxZ1aNtyYwK0ToZEL1i6TL5Dq9Qm30ZQfkA';

    public function testGetToken(): void
    {
        $token = $this->getTokenResource(false)->getToken('salesChannelId');

        $dateNow = new \DateTime('now');

        static::assertSame(CreateTokenResponseFixture::ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(CreateTokenResponseFixture::TOKEN_TYPE, $token->getTokenType());
        static::assertTrue($dateNow < $token->getExpireDateTime());
    }

    public function testGetTokenFromCache(): void
    {
        $token = $this->getTokenResource()->getToken('salesChannelId');

        static::assertSame(self::CACHED_ACCESS_TOKEN, $token->getAccessToken());
        static::assertSame(CreateTokenResponseFixture::TOKEN_TYPE, $token->getTokenType());
    }

    private function getTokenResource(bool $withToken = true): TokenResource
    {
        $cacheItemPool = $this->createMock(CacheItemPoolInterface::class);
        $item = $this->createMock(CacheItemInterface::class);
        $item->method('get')->willReturn($withToken ? $this->getCacheContent() : null);
        $cacheItemPool->method('getItem')->willReturn($item);

        $credentialProvider = $this->createMock(CredentialProviderInterface::class);
        $credentialProvider->method('createCredentialsObject')->with('salesChannelId')->willReturn($this->getCredentials());

        $logger = new NullLogger();

        return new TokenResource(
            $cacheItemPool,
            new TokenClientFactoryMock($logger),
            $credentialProvider,
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

    private function getCacheContent(): string
    {
        $expireDate = new \DateTime();
        $expireDate->add(new \DateInterval('PT5H'));
        $expireDate = $expireDate->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        return "O:32:\"Swag\PayPal\RestApi\V1\Api\Token\":7:{"
            . "s:39:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00scope\";s:275:\"https://uri.paypal.com/services/subscriptions https://api.paypal.com/v1/payments/.* https://api.paypal.com/v1/vault/credit-card https://uri.paypal.com/services/applications/webhooks openid https://uri.paypal.com/payments/payouts https://api.paypal.com/v1/vault/credit-card/.*\";"
            . "s:39:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00nonce\";s:63:\"2018-11-28T09:55:25Z-e1ZbHti0TBbVkqQDZSsZ7YkzM5rpibcPAsW8wcS-hw\";"
            . "s:45:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00accessToken\";s:97:\"" . self::CACHED_ACCESS_TOKEN . '";'
            . "s:43:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00tokenType\";s:6:\"Bearer\";"
            . "s:39:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00appId\";s:21:\"APP-80W284485P519543T\";"
            . "s:43:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00expiresIn\";i:32389;"
            . "s:48:\"\x00Swag\PayPal\RestApi\V1\Api\Token\x00expireDateTime\";O:8:\"DateTime\":3:{s:4:\"date\";s:23:\"" . $expireDate . '";s:13:"timezone_type";i:3;s:8:"timezone";s:3:"UTC";}}';
    }
}
