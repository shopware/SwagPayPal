<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Log\Recording;

use Doctrine\DBAL\Connection;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Diagnostics\Log\Recording\DatabaseLogHandler;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(DatabaseLogHandler::class)]
class DatabaseLogHandlerTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    public function testStoresTheLine(): void
    {
        $handler = $this->createHandler();

        $handler->handle(new LogRecord(
            new \DateTimeImmutable('2026-11-20 15:30:00.123', new \DateTimeZone('Europe/Berlin')),
            'paypal',
            Level::Notice,
            'Could not capture PayPal order {paypalOrderId}',
            ['code' => 'CHECKOUT_CAPTURE_FAILED', 'paypalOrderId' => '5O190127TN364715T'],
            ['uid' => 'd9e3f2a1b0'],
        ));

        $line = $this->fetchLine('CHECKOUT_CAPTURE_FAILED');
        static::assertSame(Level::Notice->value, (int) $line['level']);
        static::assertSame('Could not capture PayPal order 5O190127TN364715T', $line['message']);
        static::assertSame(['code' => 'CHECKOUT_CAPTURE_FAILED', 'paypalOrderId' => '5O190127TN364715T'], $this->decode($line['context']));
        static::assertSame(['uid' => 'd9e3f2a1b0'], $this->decode($line['extra']));
        static::assertSame('2026-11-20 14:30:00.123', $line['created_at']);
    }

    public function testStoresAnExceptionAsData(): void
    {
        $this->createHandler()->handle($this->createRecord(Level::Error, [
            'code' => 'WEBHOOK_EVENT_FAILED',
            'exception' => new \RuntimeException('PayPal could not be reached'),
        ]));

        $exception = $this->decode($this->fetchLine('WEBHOOK_EVENT_FAILED')['context'])['exception'] ?? null;
        static::assertIsArray($exception);
        static::assertSame(\RuntimeException::class, $exception['class']);
        static::assertSame('PayPal could not be reached', $exception['message']);
    }

    public function testStoresNoEmptyContextOrExtra(): void
    {
        $this->createHandler()->handle($this->createRecord(Level::Info, ['code' => 'SETTINGS_SAVED']));

        $line = $this->fetchLine('SETTINGS_SAVED');
        static::assertSame(['code' => 'SETTINGS_SAVED'], $this->decode($line['context']));
        static::assertNull($line['extra']);

        $this->createHandler()->handle($this->createRecord(Level::Info, [], 'Logged without any context'));

        $line = static::getContainer()->get(Connection::class)->fetchAssociative(
            'SELECT `code`, `context`, `extra` FROM `swag_paypal_log` WHERE `message` = :message',
            ['message' => 'Logged without any context'],
        );
        static::assertSame(['code' => null, 'context' => null, 'extra' => null], $line);
    }

    public function testTheDatabaseDerivesTheQueriedColumns(): void
    {
        $salesChannelId = Uuid::randomHex();
        $this->createHandler()->handle(new LogRecord(
            new \DateTimeImmutable(),
            'paypal',
            Level::Error,
            'A line with all keys',
            ['code' => 'DERIVED_COLUMNS', 'salesChannelId' => $salesChannelId],
            ['uid' => 'd9e3f2a'],
        ));

        $line = $this->fetchLine('DERIVED_COLUMNS');
        static::assertSame($salesChannelId, Uuid::fromBytesToHex($line['sales_channel_id']));
        static::assertSame('d9e3f2a', $line['uid']);
    }

    public function testTheDerivedColumnsAreNullWithoutTheirKeys(): void
    {
        $this->createHandler()->handle($this->createRecord(Level::Error, ['orderId' => 'abc', 'salesChannelId' => null, 'code' => null], 'A line without keys'));

        $line = static::getContainer()->get(Connection::class)->fetchAssociative(
            'SELECT `code`, `sales_channel_id`, `uid` FROM `swag_paypal_log` WHERE `message` = :message',
            ['message' => 'A line without keys'],
        );
        static::assertSame(['code' => null, 'sales_channel_id' => null, 'uid' => null], $line);
    }

    #[DataProvider('levelProvider')]
    public function testHandlesFromTheConfiguredLevel(int $setting, Level $level, bool $expected): void
    {
        $handler = $this->createHandler([Settings::LOG_LEVEL => $setting]);

        static::assertSame($expected, $handler->isHandling($this->createRecord($level)));
    }

    public static function levelProvider(): \Generator
    {
        yield 'off, even an emergency' => [0, Level::Emergency, false];
        yield 'not a level' => [150, Level::Emergency, false];
        yield 'debug' => [Level::Debug->value, Level::Debug, true];
        yield 'info, no debug' => [Level::Info->value, Level::Debug, false];
        yield 'info' => [Level::Info->value, Level::Info, true];
        yield 'notice, no info' => [Level::Notice->value, Level::Info, false];
        yield 'notice' => [Level::Notice->value, Level::Notice, true];
        yield 'notice, a critical' => [Level::Notice->value, Level::Critical, true];
        yield 'warning, no notice' => [Level::Warning->value, Level::Notice, false];
        yield 'warning' => [Level::Warning->value, Level::Warning, true];
        yield 'error, no warning' => [Level::Error->value, Level::Warning, false];
        yield 'error' => [Level::Error->value, Level::Error, true];
    }

    public function testHandlesFromTheDefaultWithoutASetting(): void
    {
        $handler = $this->createHandler([Settings::LOG_LEVEL => null]);

        static::assertFalse($handler->isHandling($this->createRecord(Level::Notice)));
        static::assertTrue($handler->isHandling($this->createRecord(Level::Warning)));
    }

    public function testDisabledByTheEnvironmentDoesNotReadTheSetting(): void
    {
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->expects($this->never())->method('get');
        $handler = new DatabaseLogHandler(static::getContainer()->get(Connection::class), $systemConfigService, false);

        static::assertFalse($handler->isHandling($this->createRecord(Level::Emergency)));
    }

    public function testHandlesNothingWhenTheSettingCannotBeRead(): void
    {
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->method('get')->willThrowException(new \RuntimeException('Database is gone'));
        $handler = new DatabaseLogHandler(static::getContainer()->get(Connection::class), $systemConfigService, true);

        static::assertFalse($handler->isHandling($this->createRecord(Level::Emergency)));
    }

    public function testAFailedWriteDoesNotFailTheLogging(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->once())->method('insert')->willThrowException(new \RuntimeException('Table does not exist'));
        $handler = new DatabaseLogHandler($connection, SystemConfigServiceMock::createWithoutCredentials(), true);

        static::assertFalse($handler->handle($this->createRecord(Level::Error)));
    }

    public function testThePayPalLoggerStoresItsLines(): void
    {
        $logger = static::getContainer()->get('monolog.logger.paypal');
        static::assertInstanceOf(LoggerInterface::class, $logger);

        $logger->warning('Saved the PayPal settings', ['code' => 'SETTINGS_SAVED_BY_THE_LOGGER']);

        static::assertSame('Saved the PayPal settings', $this->fetchLine('SETTINGS_SAVED_BY_THE_LOGGER')['message']);
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function createHandler(array $settings = [Settings::LOG_LEVEL => Level::Debug->value]): DatabaseLogHandler
    {
        return new DatabaseLogHandler(
            static::getContainer()->get(Connection::class),
            SystemConfigServiceMock::createWithoutCredentials($settings),
            true,
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function createRecord(Level $level, array $context = [], string $message = 'A PayPal log line'): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'paypal', $level, $message, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchLine(string $code): array
    {
        $line = static::getContainer()->get(Connection::class)->fetchAssociative(
            'SELECT * FROM `swag_paypal_log` WHERE `code` = :code',
            ['code' => $code],
        );
        static::assertIsArray($line);

        return $line;
    }

    /**
     * @return array<mixed>
     */
    private function decode(mixed $json): array
    {
        static::assertIsString($json);
        $decoded = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        static::assertIsArray($decoded);

        return $decoded;
    }
}
