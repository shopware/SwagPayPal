<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Payment\Service\PaymentResumeService;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentResumeService::class)]
class PaymentResumeServiceTest extends TestCase
{
    private const NOW = '2026-01-01 00:00:00';
    private const PAYPAL_ORDER_ID = 'paypalOrderId';
    private const RESUME_URL = 'https://example.test/payment/finalize-transaction?_sw_payment_token=token';

    private SystemConfigService&MockObject $systemConfigService;

    protected function setUp(): void
    {
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
    }

    public function testStoreAndConsumeRoundTrip(): void
    {
        $session = $this->createSession();
        $service = $this->createService();

        $service->store($session, self::PAYPAL_ORDER_ID, self::RESUME_URL, Uuid::randomHex());

        static::assertSame(self::RESUME_URL, $service->consume($session, self::PAYPAL_ORDER_ID));
    }

    public function testConsumeWithoutStoredResume(): void
    {
        static::assertNull($this->createService()->consume($this->createSession(), self::PAYPAL_ORDER_ID));
    }

    /**
     * A replayed return - a browser Back, say - must not reuse a finalize token core has already invalidated.
     */
    public function testConsumeRemovesTheResumeItReturns(): void
    {
        $session = $this->createSession();
        $service = $this->createService();

        $service->store($session, self::PAYPAL_ORDER_ID, self::RESUME_URL, Uuid::randomHex());

        static::assertSame(self::RESUME_URL, $service->consume($session, self::PAYPAL_ORDER_ID));
        static::assertNull($service->consume($session, self::PAYPAL_ORDER_ID));
        static::assertCount(0, $session->get(PaymentResumeService::SESSION_KEY));
    }

    /**
     * PayPal reports which order the payer returns for, so a resume must not answer for a different one.
     */
    public function testConsumeIgnoresAnotherPayPalOrder(): void
    {
        $session = $this->createSession();
        $service = $this->createService();

        $service->store($session, self::PAYPAL_ORDER_ID, self::RESUME_URL, Uuid::randomHex());

        static::assertNull($service->consume($session, 'anotherPayPalOrderId'));
    }

    /**
     * The resume of another payer lives in their own session and can never be reached from this one.
     */
    public function testConsumeIgnoresAnotherSession(): void
    {
        $service = $this->createService();
        $service->store($this->createSession(), self::PAYPAL_ORDER_ID, self::RESUME_URL, Uuid::randomHex());

        static::assertNull($service->consume($this->createSession(), self::PAYPAL_ORDER_ID));
    }

    public function testStoreReplacesAnEarlierResumeOfTheSamePayPalOrder(): void
    {
        $session = $this->createSession();
        $service = $this->createService();

        $service->store($session, self::PAYPAL_ORDER_ID, 'https://example.test/first', Uuid::randomHex());
        $service->store($session, self::PAYPAL_ORDER_ID, 'https://example.test/second', Uuid::randomHex());

        static::assertCount(1, $session->get(PaymentResumeService::SESSION_KEY));
        static::assertSame('https://example.test/second', $service->consume($session, self::PAYPAL_ORDER_ID));
    }

    /**
     * A session may create several PayPal orders, for example in two checkout tabs, but must not grow forever.
     */
    public function testStoreKeepsTheMostRecentResumesOnly(): void
    {
        $session = $this->createSession();
        $service = $this->createService();

        for ($i = 1; $i <= 10; ++$i) {
            $service->store($session, 'paypalOrderId' . $i, 'https://example.test/' . $i, Uuid::randomHex());
        }

        static::assertCount(8, $session->get(PaymentResumeService::SESSION_KEY));
        static::assertNull($service->consume($session, 'paypalOrderId1'));
        // the two oldest were dropped by the cap, not consumed
        static::assertNull($service->consume($session, 'paypalOrderId2'));
        static::assertSame('https://example.test/3', $service->consume($session, 'paypalOrderId3'));
        static::assertSame('https://example.test/10', $service->consume($session, 'paypalOrderId10'));
    }

    public function testConsumeIgnoresAnExpiredResume(): void
    {
        $session = $this->createSession();

        $this->createService('2025-12-31 23:00:00')->store($session, self::PAYPAL_ORDER_ID, self::RESUME_URL, Uuid::randomHex());

        static::assertNull($this->createService()->consume($session, self::PAYPAL_ORDER_ID));
    }

    /**
     * The resume must outlive the payment token, whose lifetime the merchant configures in the core settings.
     */
    #[DataProvider('dataProviderFinalizeTransactionTime')]
    public function testResumeExpiresWithTheFinalizeTransactionTime(int $configuredMinutes, string $stillValidAt, string $expiredAt): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->systemConfigService
            ->method('getInt')
            ->with('core.cart.paymentFinalizeTransactionTime', $salesChannelId)
            ->willReturn($configuredMinutes);

        // a consumed resume is gone, so each assertion needs a session of its own to prove the lifetime
        $stillValidSession = $this->createSession();
        $expiredSession = $this->createSession();
        $this->createService()->store($stillValidSession, self::PAYPAL_ORDER_ID, self::RESUME_URL, $salesChannelId);
        $this->createService()->store($expiredSession, self::PAYPAL_ORDER_ID, self::RESUME_URL, $salesChannelId);

        static::assertSame(self::RESUME_URL, $this->createService($stillValidAt)->consume($stillValidSession, self::PAYPAL_ORDER_ID));
        static::assertNull($this->createService($expiredAt)->consume($expiredSession, self::PAYPAL_ORDER_ID));
    }

    /**
     * @return array<string, array{int, string, string}>
     */
    public static function dataProviderFinalizeTransactionTime(): array
    {
        return [
            'default of 30 minutes' => [30, '2026-01-01 00:30:00', '2026-01-01 00:30:01'],
            'raised to two hours' => [120, '2026-01-01 02:00:00', '2026-01-01 02:00:01'],
            'unconfigured falls back to 30 minutes' => [0, '2026-01-01 00:30:00', '2026-01-01 00:30:01'],
        ];
    }

    /**
     * A session written by another plugin version must not fail the payment.
     */
    #[DataProvider('dataProviderUnusableSessionValues')]
    public function testConsumeIgnoresUnusableSessionValues(mixed $value): void
    {
        $session = $this->createSession();
        $session->set(PaymentResumeService::SESSION_KEY, $value);

        static::assertNull($this->createService()->consume($session, self::PAYPAL_ORDER_ID));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function dataProviderUnusableSessionValues(): array
    {
        return [
            'not an array' => ['string'],
            'resume is not an array' => [[self::PAYPAL_ORDER_ID => 'string']],
            'without a url' => [[self::PAYPAL_ORDER_ID => ['expiresAt' => \PHP_INT_MAX]]],
            'with an empty url' => [[self::PAYPAL_ORDER_ID => ['resumeUrl' => '', 'expiresAt' => \PHP_INT_MAX]]],
            'without an expiry' => [[self::PAYPAL_ORDER_ID => ['resumeUrl' => self::RESUME_URL]]],
            'with a non-integer expiry' => [[self::PAYPAL_ORDER_ID => ['resumeUrl' => self::RESUME_URL, 'expiresAt' => 'later']]],
        ];
    }

    private function createService(string $now = self::NOW): PaymentResumeService
    {
        return new PaymentResumeService($this->systemConfigService, new MockClock($now));
    }

    private function createSession(): SessionInterface
    {
        return new Session(new MockArraySessionStorage());
    }
}
