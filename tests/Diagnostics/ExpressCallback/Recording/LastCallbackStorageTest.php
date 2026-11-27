<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\ExpressCallback\Recording;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\CallbackOutcome;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallback;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\Clock\MockClock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LastCallbackStorage::class)]
#[CoversClass(LastCallback::class)]
class LastCallbackStorageTest extends TestCase
{
    use PayPalLogTrait;

    private const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';

    public function testKeepsTheLastCallbackPerSalesChannel(): void
    {
        $clock = new MockClock('2026-11-12 10:00:00', 'UTC');
        $storage = new LastCallbackStorage(new KeyValueStorageMock(), $clock, new NullLogger());

        $storage->record(self::SALES_CHANNEL_ID, CallbackOutcome::Answered);
        $clock->modify('+1 hour');
        $storage->record(self::SALES_CHANNEL_ID, CallbackOutcome::Rejected);

        static::assertEquals(
            new LastCallback(new \DateTimeImmutable('2026-11-12T11:00:00+00:00'), CallbackOutcome::Rejected),
            $storage->get(self::SALES_CHANNEL_ID),
        );
        static::assertNull($storage->get('0188da1fd6ee4a7f8e7d8a6eb2cbd2c1'));
    }

    public function testReadsTheJsonTheMySqlStorageReturns(): void
    {
        $keyValueStorage = new KeyValueStorageMock([
            LastCallbackStorage::KEY_PREFIX . self::SALES_CHANNEL_ID => '{"at":"2026-11-12T10:00:00+00:00","outcome":"failed"}',
        ]);

        $lastCallback = (new LastCallbackStorage($keyValueStorage, new MockClock(), new NullLogger()))->get(self::SALES_CHANNEL_ID);

        static::assertEquals(new LastCallback(new \DateTimeImmutable('2026-11-12T10:00:00+00:00'), CallbackOutcome::Failed), $lastCallback);
    }

    #[DataProvider('invalidValueProvider')]
    public function testIgnoresInvalidValues(mixed $value): void
    {
        $keyValueStorage = new KeyValueStorageMock([LastCallbackStorage::KEY_PREFIX . self::SALES_CHANNEL_ID => $value]);

        static::assertNull((new LastCallbackStorage($keyValueStorage, new MockClock(), new NullLogger()))->get(self::SALES_CHANNEL_ID));
    }

    public static function invalidValueProvider(): \Generator
    {
        yield 'no JSON' => ['answered'];
        yield 'unknown outcome' => ['{"at":"2026-11-12T10:00:00+00:00","outcome":"lost"}'];
        yield 'invalid date' => ['{"at":"yesterday","outcome":"answered"}'];
        yield 'missing outcome' => [['at' => '2026-11-12T10:00:00+00:00']];
    }

    public function testRecordingNeverBreaksTheCallback(): void
    {
        $keyValueStorage = $this->createMock(AbstractKeyValueStorage::class);
        $keyValueStorage->method('set')->willThrowException(new \RuntimeException('Database is gone'));

        (new LastCallbackStorage($keyValueStorage, new MockClock(), $this->createPayPalLogger()))->record(self::SALES_CHANNEL_ID, CallbackOutcome::Answered);

        $record = $this->assertPayPalLogged(
            LogCode::DIAGNOSTICS_CALLBACK_RECORDING_FAILED,
            Level::Warning,
            [Log::SALES_CHANNEL_ID => self::SALES_CHANNEL_ID],
            'Could not record the shipping callback, the diagnostics don\'t show it',
        );
        static::assertSame('Database is gone', $record->context[Log::EXCEPTION]['message'] ?? null);
    }
}
