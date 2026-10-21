<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Context;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use Shopware\PayPalSDK\Contract\Context\ApiContextInterface;
use Shopware\PayPalSDK\Contract\Context\CredentialsOAuthContextInterface;

/**
 * Binds a client token to one saved PayPal wallet, so the JavaScript SDK can show it
 * and let the buyer change its funding instrument.
 * The vault ID is part of the cache key, as the token cache is shared by all buyers.
 *
 * @internal
 */
#[Package('checkout')]
class VaultClaimOAuthContext implements CredentialsOAuthContextInterface
{
    public function __construct(
        private readonly CredentialsOAuthContextInterface $oauthContext,
        #[\SensitiveParameter]
        private readonly string $vaultId,
    ) {
    }

    /**
     * @return array<mixed>
     */
    public function __debugInfo(): array
    {
        return [];
    }

    public function getCacheKey(ApiContextInterface $context): ?string
    {
        $cacheKey = $this->oauthContext->getCacheKey($context);
        if ($cacheKey === null) {
            return null;
        }

        return Hasher::hash(\sprintf('vault-claim-%s-%s', $cacheKey, $this->vaultId));
    }

    public function getClientId(): string
    {
        return $this->oauthContext->getClientId();
    }

    public function getBody(): array
    {
        return [
            ...$this->oauthContext->getBody(),
            'claims[]' => 'vault_id:' . $this->vaultId,
        ];
    }

    public function getHeaders(): array
    {
        return $this->oauthContext->getHeaders();
    }
}
