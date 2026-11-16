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
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\StoreApiUrlStorage;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\KeyValueStorageMock;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(StoreApiUrlStorage::class)]
class StoreApiUrlStorageTest extends TestCase
{
    use PayPalLogTrait;

    private const SALES_CHANNEL_ID = '98432def39fc4624b33213a56b8c944d';

    #[DataProvider('callbackUrlProvider')]
    public function testRecordsTheBaseUrlPerSalesChannel(string $callbackUrl, string $baseUrl): void
    {
        $storage = new StoreApiUrlStorage(new KeyValueStorageMock(), new NullLogger());

        $storage->record(self::SALES_CHANNEL_ID, $callbackUrl);

        static::assertSame($baseUrl, $storage->get(self::SALES_CHANNEL_ID));
        static::assertNull($storage->get('0188da1fd6ee4a7f8e7d8a6eb2cbd2c1'));
    }

    public static function callbackUrlProvider(): \Generator
    {
        $path = '/store-api/paypal/express/shipping-callback/' . self::SALES_CHANNEL_ID . '/f6a2f1c4b5d34e0a9a1f2b3c4d5e6f70';

        yield 'host' => ['https://api.shop.example' . $path, 'https://api.shop.example'];
        yield 'port' => ['https://api.shop.example:8443' . $path, 'https://api.shop.example:8443'];
        yield 'base path' => ['https://shop.example/shop' . $path, 'https://shop.example/shop'];
    }

    public function testNeverStoresTheToken(): void
    {
        $keyValueStorage = new KeyValueStorageMock();
        $token = 'f6a2f1c4b5d34e0a9a1f2b3c4d5e6f70';

        (new StoreApiUrlStorage($keyValueStorage, new NullLogger()))->record(
            self::SALES_CHANNEL_ID,
            'https://api.shop.example/store-api/paypal/express/shipping-callback/' . self::SALES_CHANNEL_ID . '/' . $token,
        );

        static::assertStringNotContainsString($token, (string) \json_encode($keyValueStorage->get(StoreApiUrlStorage::KEY_PREFIX . self::SALES_CHANNEL_ID)));
    }

    public function testWritesOnlyWhenTheUrlChanged(): void
    {
        $keyValueStorage = $this->createMock(AbstractKeyValueStorage::class);
        $keyValueStorage->method('get')->willReturn('https://api.shop.example');
        $keyValueStorage->expects($this->once())->method('set')->with(StoreApiUrlStorage::KEY_PREFIX . self::SALES_CHANNEL_ID, 'https://other.shop.example');

        $storage = new StoreApiUrlStorage($keyValueStorage, new NullLogger());
        $storage->record(self::SALES_CHANNEL_ID, 'https://api.shop.example/store-api/paypal/express/shipping-callback/a/b');
        $storage->record(self::SALES_CHANNEL_ID, 'https://other.shop.example/store-api/paypal/express/shipping-callback/a/b');
    }

    public function testRecordsNothingWithoutStoreApiPath(): void
    {
        $keyValueStorage = new KeyValueStorageMock();

        (new StoreApiUrlStorage($keyValueStorage, new NullLogger()))->record(self::SALES_CHANNEL_ID, 'https://shop.example/paypal/express/shipping-callback/a/b');

        static::assertFalse($keyValueStorage->has(StoreApiUrlStorage::KEY_PREFIX . self::SALES_CHANNEL_ID));
    }

    #[DataProvider('invalidValueProvider')]
    public function testIgnoresInvalidValues(mixed $value): void
    {
        $keyValueStorage = new KeyValueStorageMock([StoreApiUrlStorage::KEY_PREFIX . self::SALES_CHANNEL_ID => $value]);

        static::assertNull((new StoreApiUrlStorage($keyValueStorage, new NullLogger()))->get(self::SALES_CHANNEL_ID));
    }

    public static function invalidValueProvider(): \Generator
    {
        yield 'empty' => [''];
        yield 'no string' => [['https://api.shop.example']];
    }

    public function testRecordingNeverBreaksTheOrderCreation(): void
    {
        $keyValueStorage = $this->createMock(AbstractKeyValueStorage::class);
        $keyValueStorage->method('set')->willThrowException(new \RuntimeException('Database is gone'));

        (new StoreApiUrlStorage($keyValueStorage, $this->createPayPalLogger()))->record(
            self::SALES_CHANNEL_ID,
            'https://api.shop.example/store-api/paypal/express/shipping-callback/a/b',
        );

        $record = $this->assertPayPalLogged(
            LogCode::DIAGNOSTICS_STORE_API_URL_RECORDING_FAILED,
            Level::Warning,
            [Log::SALES_CHANNEL_ID => self::SALES_CHANNEL_ID],
            'Could not record the Store API URL, the diagnostics can\'t test the shipping callback of the Sales Channel',
        );
        static::assertSame('Database is gone', $record->context[Log::EXCEPTION]['message'] ?? null);
    }
}
