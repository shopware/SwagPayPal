<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Resource;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Api\Authentication\Token;
use Swag\PayPal\Pos\Client\TokenClientFactory;

#[Package('checkout')]
class TokenResource
{
    private const CACHE_ID = 'pos_auth_';

    private CacheItemPoolInterface $cache;

    private TokenClientFactory $tokenClientFactory;

    /**
     * @internal
     */
    public function __construct(
        CacheItemPoolInterface $cache,
        TokenClientFactory $tokenClientFactory,
    ) {
        $this->cache = $cache;
        $this->tokenClientFactory = $tokenClientFactory;
    }

    public function getToken(OAuthCredentials $credentials): Token
    {
        $cacheId = Hasher::hash($credentials->getApiKey());
        $token = $this->getTokenFromCache($cacheId);
        if ($token === null || !$this->isTokenValid($token)) {
            $tokenClient = $this->tokenClientFactory->createTokenClient();

            $token = new Token();
            $token->assign($tokenClient->getToken($credentials));
            $this->setToken($token, $cacheId);
        }

        return $token;
    }

    public function testApiCredentials(OAuthCredentials $credentials): bool
    {
        $tokenClient = $this->tokenClientFactory->createTokenClient();

        $token = new Token();
        $token->assign($tokenClient->getToken($credentials));

        return $this->isTokenValid($token);
    }

    private function getTokenFromCache(string $cacheId): ?Token
    {
        $raw = $this->cache->getItem(self::CACHE_ID . $cacheId)->get();

        if (!\is_string($raw) || $raw === '') {
            return null;
        }

        $data = \json_decode($raw, true);

        return \is_array($data) ? $this->hydrateToken($data) : null;
    }

    private function hydrateToken(array $data): Token
    {
        $expireDateTime = $data['expireDateTime'] ?? null;
        unset($data['expireDateTime']);

        $token = (new Token())->assign($data);

        if (\is_string($expireDateTime)) {
            $token->setExpireDateTime(new \DateTime($expireDateTime));
        }

        return $token;
    }

    private function setToken(Token $token, string $cacheId): void
    {
        $item = $this->cache->getItem(self::CACHE_ID . $cacheId);

        $item->set(\json_encode([
            ...$token->jsonSerialize(),
            'expireDateTime' => $token->getExpireDateTime()->format(\DateTimeInterface::ATOM),
        ]));

        $this->cache->save($item);
    }

    private function isTokenValid(Token $token): bool
    {
        $dateTimeNow = new \DateTime('now', new \DateTimeZone('UTC'));
        $dateTimeExpire = $token->getExpireDateTime();
        // Decrease expire date by one hour just to make sure, it doesn't run into an unauthorized exception.
        $dateTimeExpire = $dateTimeExpire->sub(new \DateInterval('PT1H'));

        return $dateTimeExpire > $dateTimeNow;
    }
}
