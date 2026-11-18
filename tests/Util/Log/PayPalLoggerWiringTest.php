<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\LogKey;
use Swag\PayPal\Util\Log\PayPalLogProcessor;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversNothing]
class PayPalLoggerWiringTest extends TestCase
{
    use KernelTestBehaviour;
    use PayPalLogTrait;

    public function testPayPalLoggerHasTheLogProcessor(): void
    {
        // it redacts request data, so it must never be unwired silently
        $logger = $this->getContainer()->get('monolog.logger.paypal');

        static::assertInstanceOf(Logger::class, $logger);
        static::assertGreaterThan(0, \count(\array_filter(
            $logger->getProcessors(),
            static fn (callable $processor) => $processor instanceof PayPalLogProcessor,
        )));
    }

    public function testErrorLinesGetTheDetailsOfTheException(): void
    {
        $this->recordPayPalLogs(static::getContainer());

        static::getContainer()->get('monolog.logger.paypal')->error('Could not do it: {errorCode}', [
            LogKey::CODE => 'CODE',
            LogKey::EXCEPTION => new PayPalApiException('UNPROCESSABLE_ENTITY', 'The instrument was declined.', 422, 'INSTRUMENT_DECLINED'),
        ]);

        $record = $this->assertPayPalLogged('CODE', Level::Error, [
            LogKey::ERROR_CODE => 'SWAG_PAYPAL__API_INSTRUMENT_DECLINED',
            LogKey::PAYPAL_ISSUE => 'INSTRUMENT_DECLINED',
        ]);
        static::assertSame(PayPalApiException::class, $record->context[LogKey::EXCEPTION]['class'] ?? null);
    }
}
