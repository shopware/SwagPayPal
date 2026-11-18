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
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\Util\Log\LogKey;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversNothing]
class PayPalLogTraitTest extends TestCase
{
    use KernelTestBehaviour;
    use PayPalLogTrait;

    public function testCreatedLoggerRunsTheProcessor(): void
    {
        $this->createPayPalLogger()->info('Captured {captureId}', [
            LogKey::CODE => 'CODE',
            LogKey::PAYMENT => (new Capture())->assign(['id' => 'CAP-1']),
        ]);

        static::assertSame([['code' => 'CODE', 'captureId' => 'CAP-1']], $this->getPayPalLogContexts('CODE'));
    }

    public function testAssertLoggedComparesOnlyTheGivenKeys(): void
    {
        $this->createPayPalLogger()->warning('Something {orderNumber}', [LogKey::CODE => 'CODE', LogKey::ORDER_NUMBER => '10023', LogKey::ORDER_ID => 'id']);

        $record = $this->assertPayPalLogged('CODE', Level::Warning, [LogKey::ORDER_NUMBER => '10023'], 'Something {orderNumber}');

        static::assertSame('id', $record->context[LogKey::ORDER_ID]);
        $this->assertPayPalNotLogged('OTHER');
    }

    public function testAssertLoggedNeedsExactlyOneLine(): void
    {
        $logger = $this->createPayPalLogger();
        $logger->info('First', [LogKey::CODE => 'CODE']);
        $logger->info('Second', [LogKey::CODE => 'CODE']);

        $this->expectException(AssertionFailedError::class);
        $this->assertPayPalLogged('CODE', Level::Info);
    }

    public function testLinesWithoutCodeCanBeAsserted(): void
    {
        $this->createPayPalLogger()->debug('Debug line');

        $this->assertPayPalLogged(null, Level::Debug, message: 'Debug line');
    }

    public function testPlaceholdersWithoutValueAreFound(): void
    {
        $this->createPayPalLogger()->error('Could not pay order {orderNumber}', [LogKey::CODE => 'CODE']);

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

        $logger->notice('Container line', [LogKey::CODE => 'CODE']);
        $this->assertPayPalLogged('CODE', Level::Notice);

        $this->finishPayPalLogRecording();
        static::assertCount($handlerCount - 1, $logger->getHandlers());
    }
}
