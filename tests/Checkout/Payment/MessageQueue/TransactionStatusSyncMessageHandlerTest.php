<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\ScheduledTask;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineException;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessage;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessageHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionStatusSyncMessageHandler::class)]
class TransactionStatusSyncMessageHandlerTest extends TestCase
{
    use PayPalLogTrait;

    private EntityRepository&MockObject $orderTransactionRepository;

    private OrderTransactionStateHandler&MockObject $orderTransactionStateHandler;

    private OrderResource&MockObject $orderResource;

    private TransactionDataService&MockObject $transactionDataService;

    private TransactionStatusSyncMessageHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->orderTransactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->transactionDataService = $this->createMock(TransactionDataService::class);

        $this->handler = new TransactionStatusSyncMessageHandler(
            $this->orderTransactionRepository,
            $this->orderTransactionStateHandler,
            $this->orderResource,
            $this->transactionDataService,
            $this->createPayPalLogger(),
        );
    }

    #[DataProvider('dataProviderInvokeWithAllMatchingStatus')]
    public function testInvokeWithAllMatchingStatus(
        string $intent,
        string $status,
        ?string $stateHandlerMethod,
        string $currentState = OrderTransactionStates::STATE_UNCONFIRMED,
    ): void {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context) use ($currentState): EntitySearchResult {
                    $stateMachineState = new StateMachineStateEntity();
                    $stateMachineState->setTechnicalName($currentState);

                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');
                    $orderTransactionEntity->setStateMachineState($stateMachineState);

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

        $payPalOrder = (new Order())->assign([
            'intent' => $intent,
            'purchaseUnits' => [[
                'payments' => [
                    'captures' => [['status' => $status]],
                    'authorizations' => [['status' => $status]],
                ],
            ]],
        ]);

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($payPalOrder, 'transaction-id');

        $this->orderTransactionStateHandler
            ->expects($stateHandlerMethod ? static::once() : static::never())
            ->method($stateHandlerMethod ?? static::anything())
            ->with('transaction-id');

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);

        // the status change itself is logged by the PaymentStatusChangeSubscriber
        static::assertSame([], $this->getPayPalLogRecords());
    }

    public static function dataProviderInvokeWithAllMatchingStatus(): \Generator
    {
        yield 'intent: capture, status: completed' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_COMPLETED, 'paid'];
        yield 'intent: capture, status: declined' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_DECLINED, 'fail'];
        yield 'intent: capture, status: failed' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_FAILED, 'fail'];
        yield 'intent: capture, status: partially refunded' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_PARTIALLY_REFUNDED, null];
        yield 'intent: capture, status: pending' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_PENDING, 'process'];
        yield 'intent: capture, status: pending, already in progress' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_PENDING, null, OrderTransactionStates::STATE_IN_PROGRESS];
        yield 'intent: capture, status: pending, already authorized' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_PENDING, null, OrderTransactionStates::STATE_AUTHORIZED];
        yield 'intent: capture, status: refunded' => [PaymentIntentV2::CAPTURE, PaymentStatusV2::ORDER_CAPTURE_REFUNDED, null];

        yield 'intent: authorize, status: captured' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_CAPTURED, 'paid'];
        yield 'intent: authorize, status: created' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_CREATED, 'authorize'];
        yield 'intent: authorize, status: voided' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_VOIDED, 'cancel'];
        yield 'intent: authorize, status: denied' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_DENIED, 'fail'];
        yield 'intent: authorize, status: partially captured' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_PARTIALLY_CAPTURED, null];
        yield 'intent: authorize, status: pending' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_PENDING, 'process'];
        yield 'intent: authorize, status: pending, already in progress' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_PENDING, null, OrderTransactionStates::STATE_IN_PROGRESS];
        yield 'intent: authorize, status: created, already authorized' => [PaymentIntentV2::AUTHORIZE, PaymentStatusV2::ORDER_AUTHORIZATION_CREATED, null, OrderTransactionStates::STATE_AUTHORIZED];
    }

    public function testInvokeThrowsStateMachineExceptionException(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

        $payPalOrder = (new Order())->assign([
            'intent' => PaymentIntentV2::CAPTURE,
            'purchaseUnits' => [[
                'payments' => [
                    'captures' => [
                        ['status' => PaymentStatusV2::ORDER_CAPTURE_COMPLETED],
                    ],
                ],
            ]],
        ]);

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($payPalOrder, 'transaction-id');

        $exception = StateMachineException::illegalStateTransition(
            'invalid-state',
            'wanted-state',
            ['possible-state']
        );

        $this->orderTransactionStateHandler
            ->expects(static::once())
            ->method('paid')
            ->with('transaction-id')
            ->willThrowException($exception);

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);

        $record = $this->assertPayPalLogged(
            LogCode::STATUS_SYNC_TRANSITION_FAILED,
            Level::Error,
            [Log::PAYPAL_ORDER_ID => 'paypal-order-id', Log::ORDER_TRANSACTION_ID => 'transaction-id'],
            'The status sync could not change the payment status for PayPal order {paypalOrderId}: {errorCode}, the payment status stays unchanged',
        );
        static::assertSame($exception::class, $record->context[Log::EXCEPTION]['class']);
    }

    public function testInvokeThrowsPayPalApiException(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

        $exception = new PayPalApiException('General error', '404 Not found');

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willThrowException($exception);

        $this->orderTransactionStateHandler->expects(static::never())->method(static::anything());

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);

        $record = $this->assertPayPalLogged(
            LogCode::STATUS_SYNC_REQUEST_FAILED,
            Level::Warning,
            [Log::PAYPAL_ORDER_ID => 'paypal-order-id'],
            'The status sync could not fetch PayPal order {paypalOrderId}, it tries again with the next run',
        );
        static::assertSame($exception::class, $record->context[Log::EXCEPTION]['class']);
    }

    public function testInvokeThrowsPayPalApiExceptionResourceNotFound(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

        $exception = new PayPalApiException(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND, '404 Not found', issue: PayPalApiException::ISSUE_INVALID_RESOURCE_ID);

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willThrowException($exception);

        $this->orderTransactionStateHandler
            ->expects(static::once())
            ->method('fail')
            ->with('transaction-id');

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);

        $record = $this->assertPayPalLogged(
            LogCode::STATUS_SYNC_PAYPAL_ORDER_EXPIRED,
            Level::Info,
            [Log::PAYPAL_ORDER_ID => 'paypal-order-id'],
            'The status sync set the payment of PayPal order {paypalOrderId} to Failed, because PayPal no longer knows the order',
        );
        static::assertSame($exception::class, $record->context[Log::EXCEPTION]['class']);
    }

    public function testInvokeWithoutUnconfirmedTransaction(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static fn (Criteria $criteria, Context $context) => new EntitySearchResult('order_transaction', 0, new EntityCollection(), null, $criteria, $context)
            );

        $this->orderResource->expects(static::never())->method('get');
        $this->orderTransactionStateHandler->expects(static::never())->method(static::anything());

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);

        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testInvokeWithMissingPayPalOrderId(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

        $this->orderResource->expects(static::never())->method('get');
        $this->orderTransactionStateHandler
            ->expects(static::once())
            ->method('cancel')
            ->with('transaction-id');

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            null,
        );

        ($this->handler)($message);

        $this->assertPayPalLogged(
            LogCode::STATUS_SYNC_PAYPAL_ORDER_MISSING,
            Level::Info,
            [Log::ORDER_TRANSACTION_ID => 'transaction-id'],
            'The status sync cancelled a payment that never got a PayPal order',
        );
    }

    public function testInvokeWithMissingPayPalOrderIdSkipsTransactionThatIsNoLongerOpen(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static fn (Criteria $criteria, Context $context) => new EntitySearchResult('order_transaction', 0, new EntityCollection(), null, $criteria, $context)
            );

        $this->orderTransactionStateHandler->expects(static::never())->method(static::anything());

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', null));
    }

    #[DataProvider('dataProviderQueuedPayPalOrderId')]
    public function testInvokeOnlyLoadsTransactionWithQueuedPayPalOrderId(?string $queuedPayPalOrderId): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context) use ($queuedPayPalOrderId): EntitySearchResult {
                    static::assertContainsEquals(
                        new EqualsFilter('customFields.' . SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID, $queuedPayPalOrderId),
                        $criteria->getFilters()
                    );

                    return new EntitySearchResult('order_transaction', 0, new EntityCollection(), null, $criteria, $context);
                }
            );

        $this->orderResource->expects(static::never())->method('get');
        $this->orderTransactionStateHandler->expects(static::never())->method(static::anything());

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', $queuedPayPalOrderId));
    }

    public static function dataProviderQueuedPayPalOrderId(): \Generator
    {
        yield 'with PayPal order' => ['paypal-order-id'];
        yield 'without PayPal order' => [null];
    }

    public function testInvokeWithAuthorizedTransactionAndOrderWillSkip(): void
    {
        $this->orderTransactionRepository
            ->expects(static::once())
            ->method('search')
            ->willReturnCallback(
                static function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');
                    $stateMachineState = new StateMachineStateEntity();
                    $stateMachineState->setTechnicalName(OrderTransactionStates::STATE_AUTHORIZED);
                    $orderTransactionEntity->setStateMachineState($stateMachineState);

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

        $payPalOrder = (new Order())->assign([
            'intent' => PaymentIntentV2::AUTHORIZE,
            'purchaseUnits' => [[
                'payments' => [
                    'authorizations' => [['status' => PaymentIntentV2::AUTHORIZE]],
                ],
            ]],
        ]);

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($payPalOrder, 'transaction-id');

        $this->orderTransactionStateHandler
            ->expects(static::never())
            ->method('authorize')
            ->with('transaction-id');

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);

        static::assertSame([], $this->getPayPalLogRecords());
    }
}
