<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Service;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Storefront\Service\ReturnToken;
use Swag\PayPal\Storefront\Service\ReturnTokenService;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ReturnToken::class)]
#[CoversClass(ReturnTokenService::class)]
class ReturnTokenServiceTest extends TestCase
{
    private Configuration $configuration;

    private ClockInterface $clock;

    private SystemConfigService&MockObject $systemConfigService;

    protected function setUp(): void
    {
        $this->clock = new MockClock('2026-01-01 00:00:00');
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
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
        $service = $this->createReturnTokenService();
        $contextToken = 'context-token';
        $salesChannelId = Uuid::randomHex();

        $token = $service->generate($contextToken, $salesChannelId, ReturnToken::TARGET_CHECKOUT_CONFIRM);
        $returnToken = $service->parse($token, $salesChannelId);

        static::assertSame($contextToken, $returnToken->getContextToken());
        static::assertSame($salesChannelId, $returnToken->getSalesChannelId());
        static::assertSame(ReturnToken::TARGET_CHECKOUT_CONFIRM, $returnToken->getReturnTarget());
        static::assertNull($returnToken->getOrderId());
    }

    public function testGenerateAndParseEditOrderToken(): void
    {
        $service = $this->createReturnTokenService();
        $salesChannelId = Uuid::randomHex();
        $orderId = Uuid::randomHex();

        $token = $service->generate('context-token', $salesChannelId, ReturnToken::TARGET_ACCOUNT_ORDER_EDIT, $orderId);
        $returnToken = $service->parse($token, $salesChannelId);

        static::assertSame(ReturnToken::TARGET_ACCOUNT_ORDER_EDIT, $returnToken->getReturnTarget());
        static::assertSame($orderId, $returnToken->getOrderId());
    }

    public function testGenerateRejectsMissingEditOrderId(): void
    {
        $service = $this->createReturnTokenService();

        $this->expectException(JWTException::class);
        $service->generate('context-token', Uuid::randomHex(), ReturnToken::TARGET_ACCOUNT_ORDER_EDIT);
    }

    public function testParseRejectsExpiredToken(): void
    {
        $salesChannelId = Uuid::randomHex();
        $service = $this->createReturnTokenService(-1);
        $token = $service->generate('context-token', $salesChannelId, ReturnToken::TARGET_CHECKOUT_CONFIRM);

        $this->expectException(JWTException::class);
        $service->parse($token, $salesChannelId);
    }

    public function testParseRejectsSalesChannelMismatch(): void
    {
        $service = $this->createReturnTokenService();
        $token = $service->generate('context-token', Uuid::randomHex(), ReturnToken::TARGET_CHECKOUT_CONFIRM);

        $this->expectException(JWTException::class);
        $service->parse($token, Uuid::randomHex());
    }

    public function testParseRejectsInvalidReturnTarget(): void
    {
        $salesChannelId = Uuid::randomHex();
        $service = $this->createReturnTokenService();
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
        $service = $this->createReturnTokenService();
        $token = $this->buildToken([
            'contextToken' => 'context-token',
            'salesChannelId' => $salesChannelId,
            'returnTarget' => ReturnToken::TARGET_ACCOUNT_ORDER_EDIT,
        ]);

        $this->expectException(JWTException::class);
        $service->parse($token, $salesChannelId);
    }

    public function testRestoreContextTokenWritesSessionContext(): void
    {
        $salesChannelId = Uuid::randomHex();
        $request = new Request();
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        $this->systemConfigService
            ->expects($this->once())
            ->method('getBool')
            ->with('core.systemWideLoginRegistration.isCustomerBoundToSalesChannel', $salesChannelId)
            ->willReturn(true);

        $this->createReturnTokenService()->restoreContextToken(
            $request,
            new ReturnToken('context-token', $salesChannelId, ReturnToken::TARGET_CHECKOUT_CONFIRM),
        );

        static::assertSame('context-token', $session->get(PlatformRequest::HEADER_CONTEXT_TOKEN));
        static::assertSame('context-token', $session->get(PlatformRequest::HEADER_CONTEXT_TOKEN . '-' . $salesChannelId));
        static::assertSame($salesChannelId, $session->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID));
        static::assertSame('context-token', $request->headers->get(PlatformRequest::HEADER_CONTEXT_TOKEN));
    }

    /**
     * @param array<string, mixed> $claims
     */
    private function buildToken(array $claims): string
    {
        $now = $this->clock->now();
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

    private function createReturnTokenService(int $tokenLifetime = 900): ReturnTokenService
    {
        return new ReturnTokenService($this->configuration, $this->clock, $this->systemConfigService, $tokenLifetime);
    }
}
