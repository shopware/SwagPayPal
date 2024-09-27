<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Psr\Cache\CacheItemPoolInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\TokenClientFactoryInterface;
use Swag\PayPal\RestApi\V1\Api\Token;
use Swag\PayPal\RestApi\V1\Service\CredentialProviderInterface;
use Swag\PayPal\RestApi\V1\Service\TokenValidator;

#[Package('checkout')]
class TokenResource implements TokenResourceInterface
{
    private const CACHE_ID = 'paypal_auth_';

    /**
     * @internal
     */
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly TokenClientFactoryInterface $tokenClientFactory,
        private readonly CredentialProviderInterface $credentialProvider,
        private readonly TokenValidator $tokenValidator,
    ) {
    }

    public function getToken(?string $salesChannelId): Token
    {
        $credentials = $this->credentialProvider->createCredentialsObject($salesChannelId);

        $cacheId = \md5((string) $credentials);

        $token = $this->getTokenFromCache($cacheId);
        if ($token !== null && $this->tokenValidator->isTokenValid($token)) {
            return $token;
        }

        $tokenClient = $this->tokenClientFactory->createTokenClient($credentials);

        $token = new Token();
        $token->assign($tokenClient->getToken());
        $this->setToken($token, $cacheId);

        return $token;
    }

    public function getUserIdToken(?string $salesChannelId, ?string $targetCustomerId = null): Token
    {
        $credentials = $this->credentialProvider->createCredentialsObject($salesChannelId);
        $tokenClient = $this->tokenClientFactory->createTokenClient($credentials);

        $tokenData = ['response_type' => 'id_token'];
        if ($targetCustomerId) {
            $tokenData['target_customer_id'] = $targetCustomerId;
        }

        $token = new Token();
        $token->assign($tokenClient->getToken($tokenData));

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
