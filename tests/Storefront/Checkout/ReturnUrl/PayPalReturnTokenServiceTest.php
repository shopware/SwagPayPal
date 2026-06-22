<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Checkout\ReturnUrl;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Storefront\Checkout\ReturnUrl\PayPalReturnToken;
use Swag\PayPal\Storefront\Checkout\ReturnUrl\PayPalReturnTokenService;
use Symfony\Component\Clock\MockClock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayPalReturnToken::class)]
#[CoversClass(PayPalReturnTokenService::class)]
class PayPalReturnTokenServiceTest extends TestCase
{
    private Configuration $configuration;

    private ClockInterface $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-01-01 00:00:00');
        $key = InMemory::plainText('test-secret-with-at-least-32-bytes');
        $signer = new Sha256();
        $this->configuration = Configuration::forSymmetricSigner($signer, $key)
            ->withValidationConstraints(
                new SignedWith($signer, $key),
                new LooseValidAt($this->clock, null),
            );
    }

    public function testGenerateAndParseCheckoutConfirmToken(): void
    {
        $service = new PayPalReturnTokenService($this->configuration, $this->clock);
        $contextToken = 'context-token';
        $salesChannelId = Uuid::randomHex();

        $token = $service->generate($contextToken, $salesChannelId, PayPalReturnToken::TARGET_CHECKOUT_CONFIRM);
        $returnToken = $service->parse($token, $salesChannelId);

        static::assertSame($contextToken, $returnToken->getContextToken());
        static::assertSame($salesChannelId, $returnToken->getSalesChannelId());
        static::assertSame(PayPalReturnToken::TARGET_CHECKOUT_CONFIRM, $returnToken->getReturnTarget());
        static::assertNull($returnToken->getOrderId());
    }

    public function testGenerateAndParseEditOrderToken(): void
    {
        $service = new PayPalReturnTokenService($this->configuration, $this->clock);
        $salesChannelId = Uuid::randomHex();
        $orderId = Uuid::randomHex();

        $token = $service->generate('context-token', $salesChannelId, PayPalReturnToken::TARGET_ACCOUNT_ORDER_EDIT, $orderId);
        $returnToken = $service->parse($token, $salesChannelId);

        static::assertSame(PayPalReturnToken::TARGET_ACCOUNT_ORDER_EDIT, $returnToken->getReturnTarget());
        static::assertSame($orderId, $returnToken->getOrderId());
    }

    public function testGenerateRejectsMissingEditOrderId(): void
    {
        $service = new PayPalReturnTokenService($this->configuration, $this->clock);

        $this->expectException(JWTException::class);
        $service->generate('context-token', Uuid::randomHex(), PayPalReturnToken::TARGET_ACCOUNT_ORDER_EDIT);
    }

    public function testParseRejectsExpiredToken(): void
    {
        $salesChannelId = Uuid::randomHex();
        $service = new PayPalReturnTokenService($this->configuration, $this->clock, -1);
        $token = $service->generate('context-token', $salesChannelId, PayPalReturnToken::TARGET_CHECKOUT_CONFIRM);

        $this->expectException(JWTException::class);
        $service->parse($token, $salesChannelId);
    }

    public function testParseRejectsSalesChannelMismatch(): void
    {
        $service = new PayPalReturnTokenService($this->configuration, $this->clock);
        $token = $service->generate('context-token', Uuid::randomHex(), PayPalReturnToken::TARGET_CHECKOUT_CONFIRM);

        $this->expectException(JWTException::class);
        $service->parse($token, Uuid::randomHex());
    }

    public function testParseRejectsInvalidReturnTarget(): void
    {
        $salesChannelId = Uuid::randomHex();
        $service = new PayPalReturnTokenService($this->configuration, $this->clock);
        $token = $this->buildToken([
            'contextToken' => 'context-token',
            'salesChannelId' => $salesChannelId,
            'returnTarget' => 'invalid-target',
        ]);

        $this->expectException(JWTException::class);
        $service->parse($token, $salesChannelId);
    }

    public function testParseRejectsMissingEditOrderId(): void
    {
        $salesChannelId = Uuid::randomHex();
        $service = new PayPalReturnTokenService($this->configuration, $this->clock);
        $token = $this->buildToken([
            'contextToken' => 'context-token',
            'salesChannelId' => $salesChannelId,
            'returnTarget' => PayPalReturnToken::TARGET_ACCOUNT_ORDER_EDIT,
        ]);

        $this->expectException(JWTException::class);
        $service->parse($token, $salesChannelId);
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function buildToken(array $claims): string
    {
        $now = new \DateTimeImmutable('@' . $this->clock->now()->getTimestamp());
        $builder = $this->configuration->builder()
            ->identifiedBy(Uuid::randomHex())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($now->modify('+15 minutes'));

        foreach ($claims as $claim => $value) {
            if ($claim === '') {
                continue;
            }

            $builder = $builder->withClaim($claim, $value);
        }

        return $builder
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();
    }
}
