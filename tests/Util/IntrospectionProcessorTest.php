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
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\Kernel;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\Struct\V1\AgentErrorDetail;
use Swag\PayPal\AgentCommerce\Struct\V1\AgentErrorDetailCollection;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Pos\Api\Exception\PosException;
use Swag\PayPal\RestApi\Client\AbstractClient;
use Swag\PayPal\RestApi\Client\PayPalClient;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Storefront\Controller\PayPalController;
use Swag\PayPal\Util\IntrospectionProcessor;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

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

    #[DataProvider('invokeWithExceptionDataProvider')]
    public function testInvokeWithException(array $context, array $expected): void
    {
        $logRecord = new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Error, 'test', $context);

        $staticExcepted = [];

        foreach (['exception', 'error'] as $prop) {
            if (!$logRecord->context[$prop] instanceof \Throwable) {
                continue;
            }

            $staticExcepted[$prop] = [
                'file' => __FILE__,
                'class' => self::class . '::invokeWithExceptionDataProvider',
                'line' => $logRecord->context[$prop]->getLine(),
            ];
        }

        $processor = $this->getMockBuilder(IntrospectionProcessor::class)
            ->setConstructorArgs([Level::Error])
            ->onlyMethods(['getBacktrace'])
            ->getMock();

        $processor
            ->expects(static::once())
            ->method('getBacktrace')
            ->willReturn(\array_merge([self::TRACE_MONOLOG], []));

        $logRecord = $processor->__invoke($logRecord);

        static::assertEquals(
            \array_merge_recursive($expected, $staticExcepted),
            $logRecord->context,
        );
    }

    public static function invokeWithExceptionDataProvider(): \Generator
    {
        yield 'not a throwable' => [
            ['error' => ['some-key' => 'some-value']],
            ['error' => ['some-key' => 'some-value']],
        ];

        yield 'throwable' => [
            ['exception' => new \Exception('test-message'), 'error' => new \Exception('test-message')],
            ['exception' => ['message' => 'test-message'], 'error' => ['message' => 'test-message']],
        ];

        yield 'PayPalApiException' => [
            ['exception' => new PayPalApiException('test-name', 'test-message', issue: 'test-issue')],
            ['exception' => [
                'message' => 'The error "test-name" occurred with the following message: test-message',
                'parameters' => ['name' => 'test-name', 'message' => 'test-message', 'issue' => 'test-issue'],
                'errorCode' => 'SWAG_PAYPAL__API_test-issue',
            ]],
        ];

        yield 'PosException' => [
            ['exception' => new PosException('test-name', 'test-message')],
            ['exception' => [
                'message' => 'The error "test-name" occurred with the following message: test-message',
                'parameters' => ['name' => 'test-name', 'message' => 'test-message'],
                'errorCode' => 'SWAG_PAYPAL__POS_EXCEPTION',
            ]],
        ];

        yield 'AgentException' => [
            ['exception' => new AgentException(
                500,
                'TEST_ERROR',
                'Test error message',
                [],
                new AgentErrorDetailCollection([
                    (new AgentErrorDetail())->assign([
                        'field' => 'field1',
                        'issue' => 'issue1',
                        'description' => 'description1',
                    ]),
                    (new AgentErrorDetail())->assign([
                        'field' => 'field2',
                        'issue' => 'issue2',
                        'description' => 'description2',
                    ]),
                ])
            )],
            ['exception' => [
                'message' => 'Test error message',
                'parameters' => [],
                'errorCode' => 'TEST_ERROR',
                'details' => [
                    [
                        'field' => 'field1',
                        'issue' => 'issue1',
                        'description' => 'description1',
                    ],
                    [
                        'field' => 'field2',
                        'issue' => 'issue2',
                        'description' => 'description2',
                    ],
                ],
            ]],

        yield 'ConstraintViolationException' => [
            ['exception' => new ConstraintViolationException(new ConstraintViolationList([new ConstraintViolation(
                'test message',
                'test message template with {{ type }}',
                ['{{ type }}' => 'testParameter'],
                '/root',
                'testProperty',
                'VIOLATION_TESTPROPERTY_INVALID'
            )]), [])],
            ['exception' => [
                'message' => 'Caught 1 violation errors.',
                'parameters' => [
                    'count' => 1,
                    'violations' => ["/root.testProperty:\n    test message"],
                ],
            ]],
        ];
    }
}
