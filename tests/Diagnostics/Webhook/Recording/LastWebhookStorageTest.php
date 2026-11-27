<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Webhook\Recording;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhook;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\Recording\WebhookOutcome;
use Swag\PayPal\Diagnostics\Webhook\TestWebhook;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\Clock\MockClock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LastWebhookStorage::class)]
#[CoversClass(LastWebhook::class)]
class LastWebhookStorageTest extends TestCase
{
    use PayPalLogTrait;

    private const TOKEN = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';

    public function testKeepsTheLastWebhookPerToken(): void
    {
        $clock = new MockClock('2026-11-12 10:00:00', 'UTC');
        $storage = self::createStorage(new KeyValueStorageMock(), $clock, new NullLogger());

        $storage->record(self::TOKEN, 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled);
        $clock->modify('+1 hour');
        $storage->record(self::TOKEN, 'PAYMENT.CAPTURE.REFUNDED', WebhookOutcome::UnknownTransaction);

        static::assertEquals(
            new LastWebhook(new \DateTimeImmutable('2026-11-12T11:00:00+00:00'), 'PAYMENT.CAPTURE.REFUNDED', WebhookOutcome::UnknownTransaction, ''),
            $storage->get(self::TOKEN),
        );
        static::assertNull($storage->get('ffffffffffffffffffffffffffffffff'));
    }

    public function testKeepsTheEventsId(): void
    {
        $storage = self::createStorage(new KeyValueStorageMock(), new MockClock('2026-11-12 10:00:00', 'UTC'), new NullLogger());

        $storage->record(self::TOKEN, 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled, 'WH-123');

        $lastWebhook = $storage->get(self::TOKEN);
        static::assertSame('WH-123', $lastWebhook?->eventId);
    }

    public function testDoesNotStoreTheToken(): void
    {
        $keyValueStorage = new KeyValueStorageMock();

        self::createStorage($keyValueStorage, new MockClock(), new NullLogger())->record(self::TOKEN, 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled);

        static::assertTrue($keyValueStorage->has(LastWebhookStorage::KEY_PREFIX . \hash('xxh128', self::TOKEN)));
        static::assertFalse($keyValueStorage->has(LastWebhookStorage::KEY_PREFIX . self::TOKEN));
    }

    public function testReadsTheJsonTheMySqlStorageReturns(): void
    {
        $keyValueStorage = new KeyValueStorageMock([
            LastWebhookStorage::KEY_PREFIX . \hash('xxh128', self::TOKEN) => '{"at":"2026-11-12T10:00:00+00:00","eventType":"PAYMENT.CAPTURE.COMPLETED","outcome":"failed","eventId":"WH-1"}',
        ]);

        $lastWebhook = self::createStorage($keyValueStorage, new MockClock(), new NullLogger())->get(self::TOKEN);

        static::assertEquals(new LastWebhook(new \DateTimeImmutable('2026-11-12T10:00:00+00:00'), 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Failed, 'WH-1'), $lastWebhook);
    }

    #[DataProvider('invalidValueProvider')]
    public function testIgnoresInvalidValues(mixed $value): void
    {
        $keyValueStorage = new KeyValueStorageMock([LastWebhookStorage::KEY_PREFIX . \hash('xxh128', self::TOKEN) => $value]);

        static::assertNull(self::createStorage($keyValueStorage, new MockClock(), new NullLogger())->get(self::TOKEN));
    }

    public static function invalidValueProvider(): \Generator
    {
        yield 'no JSON' => ['handled'];
        yield 'unknown outcome' => ['{"at":"2026-11-12T10:00:00+00:00","eventType":"X","outcome":"lost","eventId":"WH-1"}'];
        yield 'invalid date' => ['{"at":"yesterday","eventType":"X","outcome":"handled","eventId":"WH-1"}'];
        yield 'missing event type' => [['at' => '2026-11-12T10:00:00+00:00', 'outcome' => 'handled', 'eventId' => 'WH-1']];
        yield 'missing event ID' => [['at' => '2026-11-12T10:00:00+00:00', 'eventType' => 'X', 'outcome' => 'handled']];
    }

    public function testRecordingNeverBreaksTheWebhook(): void
    {
        $keyValueStorage = $this->createMock(AbstractKeyValueStorage::class);
        $keyValueStorage->method('set')->willThrowException(new \RuntimeException('Database is gone'));

        self::createStorage($keyValueStorage, new MockClock(), $this->createPayPalLogger())->record(self::TOKEN, 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled, 'WH-1');

        $record = $this->assertPayPalLogged(
            LogCode::DIAGNOSTICS_WEBHOOK_RECORDING_FAILED,
            Level::Warning,
            [Log::WEBHOOK_EVENT_TYPE => 'PAYMENT.CAPTURE.COMPLETED', Log::WEBHOOK_EVENT_ID => 'WH-1'],
            'Could not record the webhook event {webhookEventType}, the diagnostics don\'t show it',
        );
        static::assertSame('Database is gone', $record->context[Log::EXCEPTION]['message'] ?? null);
    }

    public function testRecordsTheArrivalOfTheTestWebhook(): void
    {
        $keyValueStorage = new KeyValueStorageMock();
        $clock = new MockClock('2026-11-12 10:00:00', 'UTC');
        $testWebhookStorage = new TestWebhookStorage($keyValueStorage, new NullLogger());
        $storage = new LastWebhookStorage($keyValueStorage, $clock, new NullLogger(), $testWebhookStorage);
        $testWebhookStorage->expect(self::TOKEN, new TestWebhook('WH-TEST', $clock->now()));

        $storage->record(self::TOKEN, 'PAYMENT.CAPTURE.COMPLETED', WebhookOutcome::Handled, 'WH-OTHER');
        static::assertFalse($testWebhookStorage->hasArrived(self::TOKEN));

        $clock->sleep(1);
        $storage->record(self::TOKEN, 'CHECKOUT.ORDER.APPROVED', WebhookOutcome::Handled, 'WH-TEST');
        static::assertTrue($testWebhookStorage->hasArrived(self::TOKEN));
    }

    private static function createStorage(AbstractKeyValueStorage $keyValueStorage, ClockInterface $clock, LoggerInterface $logger): LastWebhookStorage
    {
        return new LastWebhookStorage($keyValueStorage, $clock, $logger, new TestWebhookStorage($keyValueStorage, new NullLogger()));
    }
}
