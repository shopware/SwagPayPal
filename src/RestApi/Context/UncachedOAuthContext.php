<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Context;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Contract\Context\ApiContextInterface;
use Shopware\PayPalSDK\Contract\Context\CredentialsOAuthContextInterface;

/**
 * Opts out of the token cache, so a fresh token is requested from PayPal.
 * Needed whenever the token itself is inspected, as a cached token still carries the scopes
 * that were granted at the time it was issued.
 *
 * @internal
 */
#[Package('checkout')]
class UncachedOAuthContext implements CredentialsOAuthContextInterface
{
    public function __construct(private readonly CredentialsOAuthContextInterface $oauthContext)
    {
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
        return null;
    }

    public function getClientId(): string
    {
        return $this->oauthContext->getClientId();
    }

    public function getBody(): array
    {
        return $this->oauthContext->getBody();
    }

    public function getHeaders(): array
    {
        return $this->oauthContext->getHeaders();
    }
}
