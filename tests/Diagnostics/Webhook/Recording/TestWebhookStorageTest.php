<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Webhook\Recording;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Webhook\Recording\TestWebhookStorage;
use Swag\PayPal\Diagnostics\Webhook\TestWebhook;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TestWebhookStorage::class)]
#[CoversClass(TestWebhook::class)]
class TestWebhookStorageTest extends TestCase
{
    use PayPalLogTrait;

    private const TOKEN = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';

    private const SENT_AT = '2026-11-12T10:00:00+00:00';

    public function testNothingArrivedWithoutATest(): void
    {
        $storage = new TestWebhookStorage(new KeyValueStorageMock(), new NullLogger());

        $storage->recordArrival(self::TOKEN, 'WH-TEST', new \DateTimeImmutable(self::SENT_AT));

        static::assertFalse($storage->hasArrived(self::TOKEN));
    }

    public function testTheTestArrivesWithItsEventId(): void
    {
        $storage = new TestWebhookStorage(new KeyValueStorageMock(), new NullLogger());
        $storage->expect(self::TOKEN, new TestWebhook('WH-TEST', new \DateTimeImmutable(self::SENT_AT)));

        $storage->recordArrival(self::TOKEN, 'WH-OTHER', new \DateTimeImmutable('2026-11-12T10:00:05+00:00'));
        static::assertFalse($storage->hasArrived(self::TOKEN));

        $storage->recordArrival(self::TOKEN, 'WH-TEST', new \DateTimeImmutable('2026-11-12T10:00:06+00:00'));
        static::assertTrue($storage->hasArrived(self::TOKEN));
        static::assertFalse($storage->hasArrived('ffffffffffffffffffffffffffffffff'));
    }

    public function testAnArrivalBeforeSendingIsNoArrival(): void
    {
        $keyValueStorage = new KeyValueStorageMock();
        $storage = new TestWebhookStorage($keyValueStorage, new NullLogger());
        $storage->expect(self::TOKEN, new TestWebhook('WH-TEST', new \DateTimeImmutable(self::SENT_AT)));
        $storage->recordArrival(self::TOKEN, 'WH-TEST', new \DateTimeImmutable('2026-11-12T10:00:05+00:00'));

        // a new test with the same event ID starts without the arrival of the old one
        $storage->expect(self::TOKEN, new TestWebhook('WH-TEST', new \DateTimeImmutable('2026-11-12T11:00:00+00:00')));

        static::assertFalse($storage->hasArrived(self::TOKEN));

        $storage->recordArrival(self::TOKEN, 'WH-TEST', new \DateTimeImmutable('2026-11-12T10:30:00+00:00'));
        static::assertFalse($storage->hasArrived(self::TOKEN));
    }

    public function testReadsTheJsonTheMySqlStorageReturns(): void
    {
        $keyValueStorage = new KeyValueStorageMock();
        $storage = new TestWebhookStorage($keyValueStorage, new NullLogger());
        $storage->expect(self::TOKEN, new TestWebhook('WH-TEST', new \DateTimeImmutable(self::SENT_AT)));
        $storage->recordArrival(self::TOKEN, 'WH-TEST', new \DateTimeImmutable('2026-11-12T10:00:05+00:00'));

        $encoded = new KeyValueStorageMock();
        foreach (['pending.', 'arrival.'] as $kind) {
            $key = TestWebhookStorage::KEY_PREFIX . $kind . \hash('xxh128', self::TOKEN);
            $encoded->set($key, \json_encode($keyValueStorage->get($key), \JSON_THROW_ON_ERROR));
        }

        static::assertTrue((new TestWebhookStorage($encoded, new NullLogger()))->hasArrived(self::TOKEN));
    }

    public function testDoesNotStoreTheToken(): void
    {
        $keyValueStorage = new KeyValueStorageMock();

        (new TestWebhookStorage($keyValueStorage, new NullLogger()))->expect(self::TOKEN, new TestWebhook('WH-TEST', new \DateTimeImmutable(self::SENT_AT)));

        static::assertTrue($keyValueStorage->has(TestWebhookStorage::KEY_PREFIX . 'pending.' . \hash('xxh128', self::TOKEN)));
        static::assertFalse($keyValueStorage->has(TestWebhookStorage::KEY_PREFIX . 'pending.' . self::TOKEN));
    }

    public function testRecordingNeverBreaksTheWebhook(): void
    {
        $keyValueStorage = $this->createMock(AbstractKeyValueStorage::class);
        $keyValueStorage->method('get')->willThrowException(new \RuntimeException('Database is gone'));

        (new TestWebhookStorage($keyValueStorage, $this->createPayPalLogger()))->recordArrival(self::TOKEN, 'WH-TEST', new \DateTimeImmutable(self::SENT_AT));

        $record = $this->assertPayPalLogged(
            LogCode::DIAGNOSTICS_WEBHOOK_TEST_RECORDING_FAILED,
            Level::Warning,
            [Log::WEBHOOK_EVENT_ID => 'WH-TEST'],
            'Could not record the arrival of the test webhook event {webhookEventId}, the diagnostics show it as missing',
        );
        static::assertSame('Database is gone', $record->context[Log::EXCEPTION]['message'] ?? null);
    }
}
