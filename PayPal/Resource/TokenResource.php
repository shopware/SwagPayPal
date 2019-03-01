<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\Api\Token;
use SwagPayPal\PayPal\Client\TokenClientFactory;

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

    public function getToken(OAuthCredentials $credentials, Context $context, string $url): Token
    {
        $token = $this->getTokenFromCache($context);
        if ($token === null || !$this->isTokenValid($token)) {
            $tokenClient = $this->tokenClientFactory->createTokenClient($credentials, $url);

            $token = new Token();
            $token->assign($tokenClient->get());
            $this->setToken($token, $context);
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

    private function getTokenFromCache(Context $context): ?Token
    {
        $token = $this->cache->getItem(self::CACHE_ID . $context->getSourceContext()->getSalesChannelId())->get();
        if ($token === null) {
            return null;
        }

        return unserialize($token, [Token::class, \DateTime::class]);
    }

    private function setToken(Token $token, Context $context): void
    {
        $item = $this->cache->getItem(self::CACHE_ID . $context->getSourceContext()->getSalesChannelId());
        $item->set(serialize($token));
        $this->cache->save($item);
    }

    private function isTokenValid(Token $token): bool
    {
        $dateTimeNow = new \DateTime();
        $dateTimeExpire = $token->getExpireDateTime();
        //Decrease expire date by one hour just to make sure, we don't run into an unauthorized exception.
        $dateTimeExpire = $dateTimeExpire->sub(new \DateInterval('PT1H'));

        return $dateTimeExpire > $dateTimeNow;
    }
}
