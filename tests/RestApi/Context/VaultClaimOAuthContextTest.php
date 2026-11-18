<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Context\ApiContext;
use Shopware\PayPalSDK\Context\ClientTokenOAuthContext;
use Shopware\PayPalSDK\Context\CredentialsOAuthContext;
use Swag\PayPal\RestApi\Context\UncachedOAuthContext;
use Swag\PayPal\RestApi\Context\VaultClaimOAuthContext;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(VaultClaimOAuthContext::class)]
class VaultClaimOAuthContextTest extends TestCase
{
    public function testBodyClaimsVault(): void
    {
        $context = new VaultClaimOAuthContext($this->createClientTokenContext(), 'vault-id');

        static::assertSame([
            'grant_type' => 'client_credentials',
            'response_type' => 'client_token',
            'claims[]' => 'vault_id:vault-id',
        ], $context->getBody());
        static::assertSame(
            'grant_type=client_credentials&response_type=client_token&claims%5B%5D=vault_id%3Avault-id',
            \http_build_query($context->getBody()),
        );
    }

    public function testCacheKeyIsBoundToVault(): void
    {
        $clientTokenContext = $this->createClientTokenContext();
        $apiContext = new ApiContext($clientTokenContext, true);

        $cacheKey = (new VaultClaimOAuthContext($clientTokenContext, 'vault-a'))->getCacheKey($apiContext);

        static::assertNotNull($cacheKey);
        static::assertSame($cacheKey, (new VaultClaimOAuthContext($clientTokenContext, 'vault-a'))->getCacheKey($apiContext));
        static::assertNotSame($cacheKey, (new VaultClaimOAuthContext($clientTokenContext, 'vault-b'))->getCacheKey($apiContext));
        static::assertNotSame($cacheKey, $clientTokenContext->getCacheKey($apiContext));
    }

    public function testUncachedContextStaysUncached(): void
    {
        $uncachedContext = new UncachedOAuthContext($this->createClientTokenContext());
        $context = new VaultClaimOAuthContext($uncachedContext, 'vault-id');

        static::assertNull($context->getCacheKey(new ApiContext($uncachedContext, true)));
    }

    public function testCredentialsArePassedThrough(): void
    {
        $clientTokenContext = $this->createClientTokenContext();
        $context = new VaultClaimOAuthContext($clientTokenContext, 'vault-id');

        static::assertSame('client-id', $context->getClientId());
        static::assertSame($clientTokenContext->getHeaders(), $context->getHeaders());
        static::assertSame([], $context->__debugInfo());
    }

    private function createClientTokenContext(): ClientTokenOAuthContext
    {
        return (new CredentialsOAuthContext('client-id', 'client-secret'))->intoClientTokenContext();
    }
}
