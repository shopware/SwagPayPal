<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Kernel;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\RestApi\Client\AbstractClient;
use Swag\PayPal\RestApi\Client\PayPalClient;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Swag\PayPal\Util\IntrospectionProcessor;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(IntrospectionProcessor::class)]
class IntrospectionProcessorTest extends TestCase
{
    private const TRACE_MONOLOG = [
        'line' => 1,
        'function' => 'error',
        'class' => Logger::class,
        'type' => '->',
    ];

    private const TRACE_ABSTRACT_CLIENT = [
        'line' => 2,
        'function' => 'request',
        'class' => AbstractClient::class,
        'type' => '->',
    ];

    private const TRACE_CLIENT = [
        'line' => 3,
        'function' => 'sendPostRequest',
        'class' => PayPalClient::class,
        'type' => '->',
    ];

    private const TRACE_RESOURCE = [
        'line' => 4,
        'function' => 'get',
        'class' => OrderResource::class,
        'type' => '->',
    ];

    private const TRACE_CONTROLLER = [
        'line' => 5,
        'function' => 'createOrder',
        'class' => PayPalController::class,
        'type' => '->',
    ];

    private const TRACE_PAYPAL = [
        'line' => 6,
        'function' => 'handlePayPalOrder',
        'class' => PayPalHandler::class,
        'type' => '->',
    ];

    private const TRACE_NOT_PAYPAL = [
        'line' => 7,
        'function' => 'handle',
        'class' => Kernel::class,
        'type' => '->',
    ];

    #[DataProvider('invokeDataProvider')]
    public function testInvoke(array $backtrace, array $expected): void
    {
        $logRecord = new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Error, 'test');

        $processor = $this->getMockBuilder(IntrospectionProcessor::class)
            ->setConstructorArgs([Level::Error])
            ->onlyMethods(['getBacktrace'])
            ->getMock();

        $processor
            ->expects(static::once())
            ->method('getBacktrace')
            ->willReturn(\array_merge([self::TRACE_MONOLOG], $backtrace));

        $processor->__invoke($logRecord);

        static::assertEquals($expected, $logRecord->extra);
    }

    public static function invokeDataProvider(): \Generator
    {
        yield 'paypal resource' => [[
            self::TRACE_RESOURCE,
            self::TRACE_PAYPAL,
        ], [
            'resource' => OrderResource::class . '->get',
            'file' => null,
            'line' => 4,
            'class' => PayPalHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'paypal controller' => [[
            self::TRACE_CONTROLLER,
            self::TRACE_NOT_PAYPAL,
        ], [
            'controller' => PayPalController::class . '->createOrder',
            'file' => null,
            'line' => 5,
            'class' => Kernel::class,
            'function' => 'handle',
        ]];

        yield 'paypal controller + resource' => [[
            self::TRACE_RESOURCE,
            self::TRACE_CONTROLLER,
            self::TRACE_NOT_PAYPAL,
        ], [
            'resource' => OrderResource::class . '->get',
            'controller' => PayPalController::class . '->createOrder',
            'file' => null,
            'line' => 5,
            'class' => Kernel::class,
            'function' => 'handle',
        ]];

        yield 'paypal client' => [[
            self::TRACE_CLIENT,
            self::TRACE_PAYPAL,
        ], [
            'client' => PayPalClient::class . '->sendPostRequest',
            'file' => null,
            'line' => 3,
            'class' => PayPalHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'paypal client + resource' => [[
            self::TRACE_RESOURCE,
            self::TRACE_CLIENT,
            self::TRACE_PAYPAL,
        ], [
            'resource' => OrderResource::class . '->get',
            'client' => PayPalClient::class . '->sendPostRequest',
            'file' => null,
            'line' => 3,
            'class' => PayPalHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'paypal client + abstract client' => [[
            self::TRACE_ABSTRACT_CLIENT,
            self::TRACE_CLIENT,
            self::TRACE_PAYPAL,
        ], [
            'client' => PayPalClient::class . '->sendPostRequest',
            'file' => null,
            'line' => 3,
            'class' => PayPalHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'paypal full' => [[
            self::TRACE_ABSTRACT_CLIENT,
            self::TRACE_CLIENT,
            self::TRACE_RESOURCE,
            self::TRACE_PAYPAL,
            self::TRACE_CONTROLLER,
            self::TRACE_NOT_PAYPAL,
        ], [
            'resource' => OrderResource::class . '->get',
            'client' => PayPalClient::class . '->sendPostRequest',
            'controller' => PayPalController::class . '->createOrder',
            'file' => null,
            'line' => 4,
            'class' => PayPalHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'not paypal trace' => [[
            self::TRACE_NOT_PAYPAL,
            self::TRACE_PAYPAL,
        ], [
            'file' => null,
            'line' => 1,
            'class' => Kernel::class,
            'function' => 'handle',
        ]];

        yield 'no traces' => [[], [
            'file' => null,
            'line' => 1,
            'class' => null,
            'function' => null,
        ]];
    }
}
