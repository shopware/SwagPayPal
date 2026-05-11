<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\CreateTokenResponseFixture;
use Swag\PayPal\Test\Pos\Mock\Client\TokenClientFactoryMock;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * @internal
 */
#[Package('checkout')]
class TokenResourceTest extends TestCase
{
    private const CACHE_ID = 'pos_auth_';

    public function testExpireDateTimeIsSerializedAsString(): void
    {
        $cache = new ArrayAdapter();
        $resource = new TokenResource($cache, new TokenClientFactoryMock());
        $credentials = $this->createCredentials();

        $resource->getToken($credentials);

        $cacheKey = self::CACHE_ID . $credentials->getCacheKey();
        $raw = $cache->getItem($cacheKey)->get();
        static::assertIsString($raw);

        $data = \json_decode($raw, true);
        static::assertIsArray($data);
        static::assertIsString(
            $data['expireDateTime'],
            'expireDateTime must be stored as an ISO string'
        );

        $restored = new \DateTime($data['expireDateTime']);
        static::assertGreaterThan(new \DateTime('now'), $restored, 'Stored expireDateTime must be in the future');
    }

    public function testValidCachedTokenIsReturnedWithoutReauth(): void
    {
        $cache = new ArrayAdapter();
        $resource = new TokenResource($cache, new TokenClientFactoryMock());

        $credentials = $this->createCredentials();

        $first = $resource->getToken($credentials);
        $second = $resource->getToken($credentials);

        static::assertSame(
            $first->getAccessToken(),
            $second->getAccessToken(),
            'The same token must be returned on the second call without re-authenticating'
        );
    }

    public function testExpiredCachedTokenTriggersReauth(): void
    {
        $cache = new ArrayAdapter();
        $resource = new TokenResource($cache, new TokenClientFactoryMock());

        $credentials = $this->createCredentials();

        $resource->getToken($credentials);

        $cacheKey = self::CACHE_ID . $credentials->getCacheKey();
        $item = $cache->getItem($cacheKey);

        $data = \json_decode($item->get(), true);
        $data['accessToken'] = 'stale-expired-token';
        $data['expireDateTime'] = (new \DateTime('-3 hours', new \DateTimeZone('UTC')))
            ->format(\DateTimeInterface::ATOM);

        $item->set(\json_encode($data));
        $cache->save($item);

        $token = $resource->getToken($credentials);

        static::assertSame(
            CreateTokenResponseFixture::ACCESS_TOKEN,
            $token->getAccessToken(),
            'An expired cached token must be replaced with a fresh one'
        );
    }

    private function createCredentials(): OAuthCredentials
    {
        $credentials = new OAuthCredentials();
        $credentials->setApiKey(ConstantsForTesting::VALID_API_KEY);

        return $credentials;
    }
}
