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
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Kernel;
use Shopware\Core\PlatformRequest;
use Shopware\PayPalSDK\Gateway\OrderGateway;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Pos\Client\AbstractClient;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Swag\PayPal\Util\IntrospectionProcessor;
use Swag\PayPal\Util\Log\Log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

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

    private const TRACE_GATEWAY = [
        'line' => 3,
        'function' => 'getOrder',
        'class' => OrderGateway::class,
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
        'class' => PayPalPaymentHandler::class,
        'type' => '->',
    ];

    private const TRACE_NOT_PAYPAL = [
        'line' => 7,
        'function' => 'handle',
        'class' => Kernel::class,
        'type' => '->',
    ];

    private RequestStack $requestStack;

    #[DataProvider('invokeDataProvider')]
    public function testInvoke(array $backtrace, array $expected): void
    {
        $logRecord = new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Error, 'test');

        $processor = $this->getMockBuilder(IntrospectionProcessor::class)
            ->setConstructorArgs([Level::Error])
            ->onlyMethods(['getBacktrace'])
            ->getMock();

        $processor
            ->expects($this->once())
            ->method('getBacktrace')
            ->willReturn(\array_merge([self::TRACE_MONOLOG], $backtrace));

        $logRecord = $processor->__invoke($logRecord);

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
            'class' => PayPalPaymentHandler::class,
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

        yield 'gateway' => [[
            self::TRACE_GATEWAY,
            self::TRACE_PAYPAL,
        ], [
            'gateway' => OrderGateway::class . '->getOrder',
            'file' => null,
            'line' => 3,
            'class' => PayPalPaymentHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'gateway + resource' => [[
            self::TRACE_RESOURCE,
            self::TRACE_GATEWAY,
            self::TRACE_PAYPAL,
        ], [
            'resource' => OrderResource::class . '->get',
            'gateway' => OrderGateway::class . '->getOrder',
            'file' => null,
            'line' => 3,
            'class' => PayPalPaymentHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'gateway + abstract client' => [[
            self::TRACE_ABSTRACT_CLIENT,
            self::TRACE_GATEWAY,
            self::TRACE_PAYPAL,
        ], [
            'gateway' => OrderGateway::class . '->getOrder',
            'file' => null,
            'line' => 3,
            'class' => PayPalPaymentHandler::class,
            'function' => 'handlePayPalOrder',
        ]];

        yield 'paypal full' => [[
            self::TRACE_ABSTRACT_CLIENT,
            self::TRACE_GATEWAY,
            self::TRACE_RESOURCE,
            self::TRACE_PAYPAL,
            self::TRACE_CONTROLLER,
            self::TRACE_NOT_PAYPAL,
        ], [
            'resource' => OrderResource::class . '->get',
            'gateway' => OrderGateway::class . '->getOrder',
            'controller' => PayPalController::class . '->createOrder',
            'file' => null,
            'line' => 4,
            'class' => PayPalPaymentHandler::class,
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

    public function testSalesChannelFromRequestUnlessTheCallSitePassedOne(): void
    {
        $processor = $this->createProcessor(new Request(attributes: [PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID => 'sales-channel-id']));

        static::assertSame('sales-channel-id', $processor($this->createRecord())->context[Log::SALES_CHANNEL_ID]);
        static::assertSame('from-context', $processor($this->createRecord([Log::SALES_CHANNEL_ID => 'from-context']))->context[Log::SALES_CHANNEL_ID]);
    }

    public function testUserFromAdminApiRequest(): void
    {
        $processor = $this->createProcessor(new Request(attributes: [
            PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT => new Context(new AdminApiSource('user-id')),
        ]));

        static::assertSame('user-id', $processor($this->createRecord())->context[Log::USER_ID]);
        static::assertSame('from-context', $processor($this->createRecord([Log::USER_ID => 'from-context']))->context[Log::USER_ID]);
    }

    public function testNoUserWithoutAdminApiUser(): void
    {
        foreach ([new AdminApiSource(null), new SystemSource(), new SalesChannelApiSource('sales-channel-id')] as $source) {
            $processor = $this->createProcessor(new Request(attributes: [PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT => new Context($source)]));

            static::assertArrayNotHasKey(Log::USER_ID, $processor($this->createRecord())->context);
        }
    }

    public function testMethodHostAndRouteOfTheMainRequestButNotItsUrl(): void
    {
        // paths and queries carry tokens, e.g. the restore token or the webhook's `sw-token`
        $request = Request::create('https://shop.example/paypal/restore-context/restore-token?sw-token=webhook-token');
        $request->attributes->set('_route', 'frontend.paypal.restore_context');
        $processor = $this->createProcessor($request);
        $this->requestStack->push(new Request(attributes: ['_route' => 'sub.request']));

        static::assertSame(
            ['uid' => 'abc', 'http_method' => 'GET', 'server' => 'shop.example', Log::ROUTE => 'frontend.paypal.restore_context'],
            $processor($this->createRecord(extra: ['uid' => 'abc']))->extra,
        );
    }

    public function testWithoutRequestTheRecordStaysAsItIs(): void
    {
        $record = $this->createRecord([Log::CODE => 'CODE']);

        static::assertSame($record, (new IntrospectionProcessor(Level::Error, new RequestStack()))($record));
        static::assertSame($record, (new IntrospectionProcessor())($record));
    }

    private function createProcessor(Request $mainRequest): IntrospectionProcessor
    {
        $this->requestStack = new RequestStack();
        $this->requestStack->push($mainRequest);

        return new IntrospectionProcessor(Level::Error, $this->requestStack);
    }

    /**
     * Below the level of the backtrace, so only the request is added.
     *
     * @param array<string, mixed> $context
     * @param array<string, mixed> $extra
     */
    private function createRecord(array $context = [], array $extra = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Info, 'message', $context, $extra);
    }
}
