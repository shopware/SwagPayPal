<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\LogKey;
use Swag\PayPal\Util\Log\PayPalLogProcessor;
use Symfony\Bridge\Monolog\Processor\WebProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

    public function testRequestUrlIsNotLogged(): void
    {
        $logger = $this->getContainer()->get('monolog.logger.paypal');
        static::assertInstanceOf(Logger::class, $logger);
        $webProcessors = \array_values(\array_filter(
            $logger->getProcessors(),
            static fn (callable $processor) => $processor instanceof WebProcessor,
        ));
        static::assertCount(1, $webProcessors);

        // a copy, so the request data doesn't stay in the shared service
        $processor = clone $webProcessors[0];
        $request = Request::create('https://shop.example/paypal/restore-context/restore-token?sw-token=webhook-token');
        $processor->onKernelRequest(new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST));

        $record = $processor(new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Info, 'message'));

        static::assertSame(['http_method' => 'GET', 'server' => 'shop.example'], $record->extra);
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
            LogKey::PAYPAL_ISSUES => ['INSTRUMENT_DECLINED'],
        ]);
        static::assertSame(PayPalApiException::class, $record->context[LogKey::EXCEPTION]['class'] ?? null);
    }
}
