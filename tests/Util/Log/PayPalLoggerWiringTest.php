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
use Shopware\Core\PlatformRequest;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\IntrospectionProcessor;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversNothing]
class PayPalLoggerWiringTest extends TestCase
{
    use KernelTestBehaviour;
    use PayPalLogTrait;

    public function testPayPalLoggerHasTheProcessorWithTheRequestStack(): void
    {
        $logger = $this->getContainer()->get('monolog.logger.paypal');
        static::assertInstanceOf(Logger::class, $logger);
        $processors = \array_values(\array_filter(
            $logger->getProcessors(),
            static fn (callable $processor) => $processor instanceof IntrospectionProcessor,
        ));
        static::assertCount(1, $processors);

        $request = new Request(attributes: [PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID => 'sales-channel-id']);
        $this->getContainer()->get('request_stack')->push($request);
        try {
            $record = $processors[0](new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Info, 'message'));
        } finally {
            $this->getContainer()->get('request_stack')->pop();
        }

        static::assertSame('sales-channel-id', $record->context[Log::SALES_CHANNEL_ID] ?? null);
    }

    public function testErrorLinesGetTheDetailsOfTheException(): void
    {
        $this->recordPayPalLogs(static::getContainer());

        static::getContainer()->get('monolog.logger.paypal')->error('Could not do it: {errorCode}', Log::context(
            code: LogCode::API_REQUEST_FAILED,
            exception: new PayPalApiException('UNPROCESSABLE_ENTITY', 'The instrument was declined.', 422, 'INSTRUMENT_DECLINED'),
        ));

        $record = $this->assertPayPalLogged(LogCode::API_REQUEST_FAILED, Level::Error, [
            Log::ERROR_CODE => 'SWAG_PAYPAL__API_INSTRUMENT_DECLINED',
            Log::PAYPAL_ISSUES => ['INSTRUMENT_DECLINED'],
        ]);
        static::assertSame(PayPalApiException::class, $record->context[Log::EXCEPTION]['class'] ?? null);
    }
}
