<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Checkout\ReturnUrl;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\UnencryptedToken;
use Psr\Clock\ClockInterface;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;

#[Package('checkout')]
class PayPalReturnTokenService
{
    private const CLAIM_CONTEXT_TOKEN = 'contextToken';
    private const CLAIM_SALES_CHANNEL_ID = 'salesChannelId';
    private const CLAIM_RETURN_TARGET = 'returnTarget';
    private const CLAIM_ORDER_ID = 'orderId';

    /**
     * @internal
     */
    public function __construct(
        private readonly Configuration $configuration,
        private readonly ClockInterface $clock,
        private readonly int $tokenLifetime = 900,
    ) {
    }

    public function generate(
        string $contextToken,
        string $salesChannelId,
        string $returnTarget,
        ?string $orderId = null,
    ): string {
        if (!\in_array($returnTarget, PayPalReturnToken::TARGETS, true)) {
            throw JWTException::invalidJwt(\sprintf('Unsupported PayPal return target "%s"', $returnTarget));
        }

        if ($returnTarget === PayPalReturnToken::TARGET_ACCOUNT_ORDER_EDIT && !$orderId) {
            throw JWTException::invalidJwt('PayPal account order edit return target requires an order id');
        }

        $now = new \DateTimeImmutable('@' . $this->clock->now()->getTimestamp());
        $expiresAt = $now->modify(\sprintf('%+d seconds', $this->tokenLifetime));

        $builder = $this->configuration->builder()
            ->identifiedBy(Uuid::randomHex())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($expiresAt)
            ->withClaim(self::CLAIM_CONTEXT_TOKEN, $contextToken)
            ->withClaim(self::CLAIM_SALES_CHANNEL_ID, $salesChannelId)
            ->withClaim(self::CLAIM_RETURN_TARGET, $returnTarget);

        if ($orderId !== null) {
            $builder = $builder->withClaim(self::CLAIM_ORDER_ID, $orderId);
        }

        return $builder
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();
    }

    public function parse(string $token, string $expectedSalesChannelId): PayPalReturnToken
    {
        if ($token === '') {
            throw JWTException::invalidJwt('JWT cannot be empty');
        }

        try {
            $jwt = $this->configuration->parser()->parse($token);
        } catch (\Exception $e) {
            throw JWTException::invalidJwt('Failed to parse JWT: ' . $e->getMessage(), $e);
        }

        if (!$jwt instanceof UnencryptedToken) {
            throw JWTException::invalidJwt('JWT is not an unencrypted token');
        }

        if (!$this->configuration->validator()->validate($jwt, ...$this->configuration->validationConstraints())) {
            throw JWTException::invalidJwt('JWT validation failed');
        }

        $claims = $jwt->claims()->all();
        $contextToken = $this->getRequiredStringClaim($claims, self::CLAIM_CONTEXT_TOKEN);
        $salesChannelId = $this->getRequiredStringClaim($claims, self::CLAIM_SALES_CHANNEL_ID);
        $returnTarget = $this->getRequiredStringClaim($claims, self::CLAIM_RETURN_TARGET);
        $orderId = $claims[self::CLAIM_ORDER_ID] ?? null;

        if ($salesChannelId !== $expectedSalesChannelId) {
            throw JWTException::invalidJwt('JWT sales channel does not match current sales channel');
        }

        if (!\in_array($returnTarget, PayPalReturnToken::TARGETS, true)) {
            throw JWTException::invalidJwt(\sprintf('Unsupported PayPal return target "%s"', $returnTarget));
        }

        if ($returnTarget === PayPalReturnToken::TARGET_ACCOUNT_ORDER_EDIT) {
            if (!\is_string($orderId) || !Uuid::isValid($orderId)) {
                throw JWTException::invalidJwt('PayPal account order edit return target requires a valid order id');
            }
        } else {
            $orderId = null;
        }

        return new PayPalReturnToken($contextToken, $salesChannelId, $returnTarget, $orderId);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function getRequiredStringClaim(array $claims, string $claim): string
    {
        $value = $claims[$claim] ?? null;
        if (!\is_string($value) || $value === '') {
            throw JWTException::invalidJwt(\sprintf('JWT claim "%s" is missing or invalid', $claim));
        }

        return $value;
    }
}
