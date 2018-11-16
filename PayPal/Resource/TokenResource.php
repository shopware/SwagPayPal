<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use DateInterval;
use DateTime;
use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Client\TokenClient;
use SwagPayPal\PayPal\Struct\OAuthCredentials;
use SwagPayPal\PayPal\Struct\Token;

class TokenResource
{
    public const CACHE_ID = 'paypal_auth_';

    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getToken(OAuthCredentials $credentials, Context $context, string $url): Token
    {
        $token = $this->getTokenFromCache($context);
        if ($token === null || !$this->isTokenValid($token)) {
            $tokenClient = new TokenClient($credentials, $url);

            $token = Token::fromArray($tokenClient->get());
            $this->setToken($token, $context);
        }

        return $token;
    }

    private function getTokenFromCache(Context $context)
    {
        $token = $this->cache->getItem(self::CACHE_ID . $context->getSourceContext()->getSalesChannelId())->get();
        if ($token === null) {
            return $token;
        }

        return unserialize(
            $this->cache->getItem(self::CACHE_ID . $context->getSourceContext()->getSalesChannelId())->get(),
            [Token::class]
        );
    }

    private function setToken(Token $token, Context $context): void
    {
        $item = $this->cache->getItem(self::CACHE_ID . $context->getSourceContext()->getSalesChannelId());
        $item->set(serialize($token));
        $this->cache->save($item);
    }

    private function isTokenValid(Token $token): bool
    {
        $dateTimeNow = new DateTime();
        $dateTimeExpire = $token->getExpireDateTime();
        //Decrease expire date by one hour just to make sure, we don't run into an unauthorized exception.
        $dateTimeExpire = $dateTimeExpire->sub(new DateInterval('PT1H'));

        if ($dateTimeExpire < $dateTimeNow) {
            return false;
        }

        return true;
    }
}
