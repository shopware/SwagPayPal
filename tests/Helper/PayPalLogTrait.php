<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\After;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\LogKey;
use Swag\PayPal\Util\Log\PayPalLogProcessor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Records log lines as the `paypal` channel writes them, after the `PayPalLogProcessor`.
 * Every recorded message must have a context value for each of its placeholders.
 *
 * @internal
 */
#[Package('checkout')]
trait PayPalLogTrait
{
    private ?TestHandler $payPalLogHandler = null;

    private ?Logger $recordedPayPalLogger = null;

    /**
     * For services built in the test.
     */
    protected function createPayPalLogger(): LoggerInterface
    {
        return new Logger('paypal', [$this->getPayPalLogHandler()], [new PayPalLogProcessor(new RequestStack())]);
    }

    /**
     * For services from the container, with every processor of the `paypal` channel.
     */
    protected function recordPayPalLogs(ContainerInterface $container): void
    {
        $logger = $container->get('monolog.logger.paypal');
        static::assertInstanceOf(Logger::class, $logger);

        $logger->pushHandler($this->getPayPalLogHandler());
        $this->recordedPayPalLogger = $logger;
    }

    /**
     * @return list<LogRecord>
     */
    protected function getPayPalLogRecords(?string $code = null): array
    {
        return \array_values(\array_filter(
            $this->getPayPalLogHandler()->getRecords(),
            static fn (LogRecord $record): bool => $code === null || ($record->context[LogKey::CODE] ?? null) === $code,
        ));
    }

    /**
     * @return list<array<array-key, mixed>>
     */
    protected function getPayPalLogContexts(string $code): array
    {
        return \array_map(static fn (LogRecord $record): array => $record->context, $this->getPayPalLogRecords($code));
    }

    /**
     * Asserts exactly one line with the code and level, compares only the given context keys.
     *
     * @param array<string, mixed> $context
     */
    protected function assertPayPalLogged(?string $code, Level $level, array $context = [], ?string $message = null): LogRecord
    {
        $records = \array_values(\array_filter(
            $this->getPayPalLogHandler()->getRecords(),
            static fn (LogRecord $record): bool => ($record->context[LogKey::CODE] ?? null) === $code && $record->level === $level,
        ));
        static::assertCount(1, $records, \sprintf('Expected one %s line with code "%s"', $level->getName(), $code ?? 'none'));

        $record = $records[0];
        if ($message !== null) {
            static::assertSame($message, $record->message);
        }

        foreach ($context as $key => $value) {
            static::assertArrayHasKey($key, $record->context);
            static::assertSame($value, $record->context[$key], \sprintf('Context key "%s"', $key));
        }

        return $record;
    }

    protected function assertPayPalNotLogged(string $code): void
    {
        static::assertSame([], $this->getPayPalLogRecords($code), \sprintf('Unexpected line with code "%s"', $code));
    }

    /**
     * @return list<string>
     */
    protected function getPayPalLogMessagesWithUnfilledPlaceholders(): array
    {
        $messages = [];
        foreach ($this->getPayPalLogHandler()->getRecords() as $record) {
            \preg_match_all('/{([\w.]+)}/', $record->message, $placeholders);
            if (\array_diff($placeholders[1], \array_keys($record->context)) !== []) {
                $messages[] = $record->message;
            }
        }

        return $messages;
    }

    #[After]
    protected function finishPayPalLogRecording(): void
    {
        if ($this->recordedPayPalLogger !== null) {
            $handler = $this->payPalLogHandler;
            $this->recordedPayPalLogger->setHandlers(\array_values(\array_filter(
                $this->recordedPayPalLogger->getHandlers(),
                static fn (object $existing): bool => $existing !== $handler,
            )));
            $this->recordedPayPalLogger = null;
        }

        // a failing after hook replaces the test's own failure
        if ($this->payPalLogHandler !== null && $this->status()->isSuccess()) {
            static::assertSame([], $this->getPayPalLogMessagesWithUnfilledPlaceholders(), 'Log messages with placeholders without a context value');
        }
    }

    private function getPayPalLogHandler(): TestHandler
    {
        return $this->payPalLogHandler ??= new TestHandler();
    }
}
