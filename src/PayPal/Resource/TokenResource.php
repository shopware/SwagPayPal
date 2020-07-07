<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use Psr\Cache\CacheItemPoolInterface;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\Api\Token;
use Swag\PayPal\PayPal\Client\CredentialsClientFactory;
use Swag\PayPal\PayPal\Client\TokenClientFactory;

class TokenResource
{
    private const CACHE_ID = 'paypal_auth_';

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var TokenClientFactory
     */
    private $tokenClientFactory;

    /**
     * @var CredentialsClientFactory
     */
    private $credentialsClientFactory;

    public function __construct(
        CacheItemPoolInterface $cache,
        TokenClientFactory $tokenClientFactory,
        CredentialsClientFactory $credentialsClientFactory
    ) {
        $this->cache = $cache;
        $this->tokenClientFactory = $tokenClientFactory;
        $this->credentialsClientFactory = $credentialsClientFactory;
    }

    public function getToken(OAuthCredentials $credentials, string $url): Token
    {
        $cacheId = \md5((string) $credentials);
        $token = $this->getTokenFromCache($cacheId);
        if ($token === null || !$this->isTokenValid($token)) {
            $tokenClient = $this->tokenClientFactory->createTokenClient($credentials, $url);

            $token = new Token();
            $token->assign($tokenClient->getToken());
            $this->setToken($token, $cacheId);
        }

        return $token;
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

        return $this->isTokenValid($token);
    }

    private function getTokenFromCache(string $cacheId): ?Token
    {
        $token = $this->cache->getItem(\sprintf('%s%s', self::CACHE_ID, $cacheId))->get();
        if ($token === null) {
            return null;
        }

        return \unserialize($token, ['allowed_classes' => [Token::class, \DateTime::class]]);
    }

    private function setToken(Token $token, string $cacheId): void
    {
        $item = $this->cache->getItem(self::CACHE_ID . $cacheId);
        $item->set(\serialize($token));
        $this->cache->save($item);
    }

    private function isTokenValid(Token $token): bool
    {
        $dateTimeNow = new \DateTime();
        $dateTimeExpire = $token->getExpireDateTime();
        // Decrease expire date by one hour just to make sure, it doesn't run into an unauthorized exception.
        $dateTimeExpire = $dateTimeExpire->sub(new \DateInterval('PT1H'));

        return $dateTimeExpire > $dateTimeNow;
    }
}
