<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Resource;

use Psr\Cache\CacheItemPoolInterface;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\Api\Token;
use Swag\PayPal\PayPal\Client\TokenClientFactory;

class TokenResource
{
    public const CACHE_ID = 'paypal_auth_';

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * @var TokenClientFactory
     */
    private $tokenClientFactory;

    public function __construct(CacheItemPoolInterface $cache, TokenClientFactory $tokenClientFactory)
    {
        $this->cache = $cache;
        $this->tokenClientFactory = $tokenClientFactory;
    }

    public function getToken(OAuthCredentials $credentials, string $url): Token
    {
        $cacheId = md5((string) $credentials);
        $token = $this->getTokenFromCache($cacheId);
        if ($token === null || !$this->isTokenValid($token)) {
            $tokenClient = $this->tokenClientFactory->createTokenClient($credentials, $url);

            $token = new Token();
            $token->assign($tokenClient->get());
            $this->setToken($token, $cacheId);
        }

        return $token;
    }

    public function testApiCredentials(OAuthCredentials $credentials, string $url): bool
    {
        $tokenClient = $this->tokenClientFactory->createTokenClient($credentials, $url);

        $token = new Token();
        $token->assign($tokenClient->get());

        return $this->isTokenValid($token);
    }

    private function getTokenFromCache(string $cacheId): ?Token
    {
        $token = $this->cache->getItem(self::CACHE_ID . $cacheId)->get();
        if ($token === null) {
            return null;
        }

        return unserialize($token, ['allowed_classes' => [Token::class, \DateTime::class]]);
    }

    private function setToken(Token $token, string $cacheId): void
    {
        $item = $this->cache->getItem(self::CACHE_ID . $cacheId);
        $item->set(serialize($token));
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
