<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper;

use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversNothing]
class PayPalLogTraitTest extends TestCase
{
    use KernelTestBehaviour;
    use PayPalLogTrait;

    public function testCreatedLoggerRecordsTheContextAsGiven(): void
    {
        // without a request, the processor has nothing to add
        $this->createPayPalLogger()->info('Could not do it: {errorCode}', Log::context(code: LogCode::API_REQUEST_FAILED, exception: new \RuntimeException('message')));

        static::assertSame('RuntimeException', $this->getPayPalLogContexts(LogCode::API_REQUEST_FAILED)[0][Log::ERROR_CODE] ?? null);
    }

    public function testAssertLoggedComparesOnlyTheGivenKeys(): void
    {
        $this->createPayPalLogger()->warning('Something {orderNumber}', [Log::CODE => LogCode::API_REQUEST_FAILED, Log::ORDER_NUMBER => '10023', Log::ORDER_ID => 'id']);

        $record = $this->assertPayPalLogged(LogCode::API_REQUEST_FAILED, Level::Warning, [Log::ORDER_NUMBER => '10023'], 'Something {orderNumber}');

        static::assertSame('id', $record->context[Log::ORDER_ID]);
        $this->assertPayPalNotLogged('OTHER');
    }

    public function testAssertLoggedNeedsExactlyOneLine(): void
    {
        $logger = $this->createPayPalLogger();
        $logger->info('First', [Log::CODE => LogCode::API_REQUEST_FAILED]);
        $logger->info('Second', [Log::CODE => LogCode::API_REQUEST_FAILED]);

        $this->expectException(AssertionFailedError::class);
        $this->assertPayPalLogged(LogCode::API_REQUEST_FAILED, Level::Info);
    }

    public function testLinesWithoutCodeCanBeAsserted(): void
    {
        $this->createPayPalLogger()->debug('Debug line');

        $this->assertPayPalLogged(null, Level::Debug, message: 'Debug line');
    }

    public function testPlaceholdersWithoutValueAreFound(): void
    {
        $this->createPayPalLogger()->error('Could not pay order {orderNumber}', [Log::CODE => LogCode::API_REQUEST_FAILED]);

        static::assertSame(['Could not pay order {orderNumber}'], $this->getPayPalLogMessagesWithUnfilledPlaceholders());

        // the after hook would fail on the same line
        $this->getPayPalLogHandler()->clear();
    }

    public function testRecordsTheContainerLoggerAndDetachesAfterwards(): void
    {
        $this->recordPayPalLogs(static::getContainer());
        $logger = static::getContainer()->get('monolog.logger.paypal');
        static::assertInstanceOf(Logger::class, $logger);
        $handlerCount = \count($logger->getHandlers());

        $logger->notice('Container line', [Log::CODE => LogCode::API_REQUEST_FAILED]);
        $this->assertPayPalLogged(LogCode::API_REQUEST_FAILED, Level::Notice);

        $this->finishPayPalLogRecording();
        static::assertCount($handlerCount - 1, $logger->getHandlers());
    }
}
