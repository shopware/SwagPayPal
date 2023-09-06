<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\TokenClientFactory;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;

#[Package('checkout')]
class TokenResource implements TokenResourceInterface
{
    private const CACHE_ID = 'paypal_auth_';

    private CacheItemPoolInterface $cache;

    private TokenClientFactory $tokenClientFactory;

    private TokenValidator $tokenValidator;

    /**
     * @internal
     */
    public function __construct(
        CacheItemPoolInterface $cache,
        TokenClientFactory $tokenClientFactory,
        TokenValidator $tokenValidator
    ) {
        $this->cache = $cache;
        $this->tokenClientFactory = $tokenClientFactory;
        $this->tokenValidator = $tokenValidator;
    }

    public function getToken(OAuthCredentials $credentials): Token
    {
        $cacheId = \md5((string) $credentials);
        $token = $this->getTokenFromCache($cacheId);
        if ($token === null || !$this->tokenValidator->isTokenValid($token)) {
            $tokenClient = $this->tokenClientFactory->createTokenClient($credentials);

            $token = new Token();
            $token->assign($tokenClient->getToken());
            $this->setToken($token, $cacheId);
        }

        return $token;
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
}
