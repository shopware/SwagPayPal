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
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineException;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessage;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessageHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionStatusSyncMessageHandler::class)]
class TransactionStatusSyncMessageHandlerTest extends TestCase
{
    /**
     * @var StaticEntityRepository<OrderTransactionCollection>
     */
    private StaticEntityRepository $orderTransactionRepository;

    private OrderTransactionStateHandler&MockObject $orderTransactionStateHandler;

    private OrderResource&MockObject $orderResource;

    private TransactionDataService&MockObject $transactionDataService;

    private LoggerInterface&MockObject $logger;

    private OrderExecuteService&MockObject $orderExecuteService;

    private TransactionStatusSyncMessageHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = new StaticEntityRepository([], new OrderTransactionDefinition());
        $this->orderTransactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->transactionDataService = $this->createMock(TransactionDataService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->orderExecuteService = $this->createMock(OrderExecuteService::class);
        $this->orderExecuteService->expects($this->never())->method('captureOrAuthorizeOrder');

        $this->handler = new TransactionStatusSyncMessageHandler(
            $this->orderTransactionRepository,
            $this->orderTransactionStateHandler,
            $this->orderResource,
            $this->transactionDataService,
            $this->logger,
            $this->orderExecuteService,
        );
    }

    #[DataProvider('dataProviderInvokeWithAllMatchingStatus')]
    public function testInvokeWithAllMatchingStatus(string $intent, string $status, ?string $stateHandlerMethod): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id']);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));

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
            ->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($payPalOrder, 'transaction-id');

        $this->orderTransactionStateHandler
            ->expects($stateHandlerMethod ? $this->once() : $this->never())
            ->method($stateHandlerMethod ?? static::anything())
            ->with('transaction-id');

        $this->logger->expects($this->never())->method(static::anything());

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);
    }

    public static function dataProviderInvokeWithAllMatchingStatus(): \Generator
    {
        yield 'intent: capture, status: completed' => [ConstantsV2::INTENT_CAPTURE, ConstantsV2::ORDER_CAPTURE_COMPLETED, 'paid'];
        yield 'intent: capture, status: declined' => [ConstantsV2::INTENT_CAPTURE, ConstantsV2::ORDER_CAPTURE_DECLINED, 'fail'];
        yield 'intent: capture, status: failed' => [ConstantsV2::INTENT_CAPTURE, ConstantsV2::ORDER_CAPTURE_FAILED, 'fail'];
        yield 'intent: capture, status: partially refunded' => [ConstantsV2::INTENT_CAPTURE, ConstantsV2::ORDER_CAPTURE_PARTIALLY_REFUNDED, null];
        yield 'intent: capture, status: pending' => [ConstantsV2::INTENT_CAPTURE, ConstantsV2::ORDER_CAPTURE_PENDING, null];
        yield 'intent: capture, status: refunded' => [ConstantsV2::INTENT_CAPTURE, ConstantsV2::ORDER_CAPTURE_REFUNDED, null];

        yield 'intent: authorize, status: captured' => [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::ORDER_AUTHORIZATION_CAPTURED, 'paid'];
        yield 'intent: authorize, status: created' => [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::ORDER_AUTHORIZATION_CREATED, 'authorize'];
        yield 'intent: authorize, status: voided' => [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::ORDER_AUTHORIZATION_VOIDED, 'cancel'];
        yield 'intent: authorize, status: denied' => [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::ORDER_AUTHORIZATION_DENIED, 'fail'];
        yield 'intent: authorize, status: partially captured' => [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::ORDER_AUTHORIZATION_PARTIALLY_CAPTURED, null];
        yield 'intent: authorize, status: pending' => [ConstantsV2::INTENT_AUTHORIZE, ConstantsV2::ORDER_AUTHORIZATION_PENDING, null];
    }

    public function testInvokeThrowsStateMachineExceptionException(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id']);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));

        $payPalOrder = (new Order())->assign([
            'intent' => ConstantsV2::INTENT_CAPTURE,
            'purchaseUnits' => [[
                'payments' => [
                    'captures' => [
                        ['status' => ConstantsV2::ORDER_CAPTURE_COMPLETED],
                    ],
                ],
            ]],
        ]);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($payPalOrder, 'transaction-id');

        $exception = StateMachineException::illegalStateTransition(
            'invalid-state',
            'wanted-state',
            ['possible-state']
        );

        $this->orderTransactionStateHandler
            ->expects($this->once())
            ->method('paid')
            ->with('transaction-id')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                Level::Error,
                'Failed to synchronise transaction status for "transaction-id": Illegal transition "wanted-state" from state "invalid-state". Possible transitions are: possible-state',
                ['error' => $exception]
            );

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);
    }

    public function testInvokeThrowsPayPalApiException(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id']);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));

        $exception = new PayPalApiException('General error', '404 Not found');

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('log')
            ->with(
                Level::Warning,
                'Failed to synchronise transaction status for "transaction-id": The error "General error" occurred with the following message: 404 Not found',
                ['error' => $exception]
            );

        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);
    }

    public function testInvokeThrowsPayPalApiExceptionResourceNotFound(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id']);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));

        $exception = new PayPalApiException(PayPalApiException::ERROR_CODE_RESOURCE_NOT_FOUND, '404 Not found', issue: PayPalApiException::ISSUE_INVALID_RESOURCE_ID);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willThrowException($exception);

        $this->logger->expects($this->never())->method(static::anything());

        $this->orderTransactionStateHandler
            ->expects($this->once())
            ->method('fail')
            ->with('transaction-id');

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);
    }

    public function testInvokeWithoutUnconfirmedTransaction(): void
    {
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection());

        $this->orderResource->expects($this->never())->method('get');
        $this->logger->expects($this->never())->method(static::anything());
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);
    }

    public function testInvokeWithMissingPayPalOrderId(): void
    {
        $this->orderResource->expects($this->never())->method('get');
        $this->logger->expects($this->never())->method(static::anything());

        $this->orderTransactionStateHandler
            ->expects($this->once())
            ->method('cancel')
            ->with('transaction-id');

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            null,
        );

        ($this->handler)($message);
    }

    public function testRequestedCancellationIsRetriedAfterVerificationFailure(): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_UNCONFIRMED);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
        ]);
        $this->orderTransactionRepository->addSearch(
            new OrderTransactionCollection([$transaction]),
            new OrderTransactionCollection([$transaction]),
        );
        $this->orderExecuteService->expects($this->exactly(2))->method('isCancellationAllowed')
            ->with('paypal-order-id', 'sales-channel-id', 'transaction-id', static::isInstanceOf(Context::class))
            ->willReturn(false, true);
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'status' => 'CREATED',
            'purchase_units' => [['reference_id' => 'default']],
        ]);
        $this->orderResource->expects($this->once())->method('get')->willReturn($order);
        $this->transactionDataService->expects($this->once())->method('setResourceId');
        $cancellations = $this->once();
        $this->orderTransactionStateHandler->expects($cancellations)->method('cancel')->with('transaction-id');

        $message = new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'paypal-order-id');
        ($this->handler)($message);
        static::assertSame(0, $cancellations->numberOfInvocations());
        ($this->handler)($message);
        static::assertSame(1, $cancellations->numberOfInvocations());
        static::assertSame([], $this->orderTransactionRepository->updates);
    }

    public function testRequestedCancellationOfUnexecutedApprovedOrderDoesNotCapture(): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_OPEN);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->once())->method('isCancellationAllowed')
            ->with('paypal-order-id', 'sales-channel-id', 'transaction-id', static::isInstanceOf(Context::class))
            ->willReturn(true);
        $this->orderTransactionStateHandler->expects($this->once())->method('cancel')->with('transaction-id');
        $this->orderResource->expects($this->never())->method(static::anything());
        $this->transactionDataService->expects($this->never())->method('setResourceId');

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'paypal-order-id'));
    }

    #[DataProvider('pendingCancellationPaymentProvider')]
    public function testSubmittedPaymentWithRequestedCancellationStillReconciles(string $intent, string $status, ?string $transition): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_IN_PROGRESS);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->once())->method('isCancellationAllowed')->willReturn(false);
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'intent' => $intent,
            'purchase_units' => [['payments' => [
                $intent === ConstantsV2::INTENT_CAPTURE ? 'captures' : 'authorizations' => [['id' => 'resource-id', 'status' => $status]],
            ]]],
        ]);
        $this->orderResource->expects($this->once())->method('get')->willReturn($order);
        $this->transactionDataService->expects($this->once())->method('setResourceId')->with($order, 'transaction-id');
        $this->orderTransactionStateHandler->expects($transition ? $this->once() : $this->never())
            ->method($transition ?? static::anything())->with('transaction-id');
        $this->orderTransactionStateHandler->expects($this->never())->method('cancel');

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'paypal-order-id'));
    }

    public static function pendingCancellationPaymentProvider(): \Generator
    {
        yield 'pending capture remains pending' => [ConstantsV2::INTENT_CAPTURE, 'PENDING', null];
        yield 'completed capture marks transaction paid' => [ConstantsV2::INTENT_CAPTURE, 'COMPLETED', 'paid'];
        yield 'pending authorization remains pending' => [ConstantsV2::INTENT_AUTHORIZE, 'PENDING', null];
        yield 'created authorization marks transaction authorized' => [ConstantsV2::INTENT_AUTHORIZE, 'CREATED', 'authorize'];
    }

    #[DataProvider('staleCancellationProvider')]
    public function testStaleCancellationCannotAffectAnotherPayPalOrder(string $currentOrderId, string $requestedOrderId, string $messageOrderId): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_UNCONFIRMED);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $currentOrderId,
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => $requestedOrderId,
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->never())->method('isCancellationAllowed');
        $this->orderResource->expects($this->never())->method('get');
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());
        $this->transactionDataService->expects($this->never())->method('setResourceId');

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', $messageOrderId));

        static::assertSame([], $this->orderTransactionRepository->updates);
    }

    public static function staleCancellationProvider(): \Generator
    {
        yield 'old message cannot cancel a replacement order' => ['replacement-order', 'old-order', 'old-order'];
        yield 'old message cannot clear a newer cancellation' => ['replacement-order', 'replacement-order', 'old-order'];
    }

    public function testOldCancellationMessageCannotReconcileReplacementAfterMarkerWasCleared(): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_UNCONFIRMED);
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'replacement-order']);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->never())->method('isCancellationAllowed');
        $this->orderResource->expects($this->never())->method('get');
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());
        $this->transactionDataService->expects($this->never())->method('setResourceId');

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'old-order'));

        static::assertSame([], $this->orderTransactionRepository->updates);
    }

    public function testOlderCancellationRequestDoesNotPreventCurrentPaymentReconciliation(): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_UNCONFIRMED);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'replacement-order',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'old-order',
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->never())->method('isCancellationAllowed');
        $order = (new Order())->assign([
            'id' => 'replacement-order',
            'intent' => 'CAPTURE',
            'purchase_units' => [['payments' => ['captures' => [['id' => 'capture-id', 'status' => 'COMPLETED']]]]],
        ]);
        $this->orderResource->expects($this->once())->method('get')->with('replacement-order', 'sales-channel-id')->willReturn($order);
        $this->transactionDataService->expects($this->once())->method('setResourceId')->with($order, 'transaction-id');
        $this->orderTransactionStateHandler->expects($this->once())->method('paid')->with('transaction-id');
        $this->orderTransactionStateHandler->expects($this->never())->method('cancel');

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'replacement-order'));

        static::assertSame([], $this->orderTransactionRepository->updates);
    }

    #[DataProvider('settledTransactionProvider')]
    public function testPendingCancellationNeverChangesSettledTransaction(string $state): void
    {
        $transaction = $this->createTransaction($state);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->never())->method('isCancellationAllowed');
        $this->orderResource->expects($this->never())->method('get');
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'paypal-order-id'));
    }

    public static function settledTransactionProvider(): \Generator
    {
        yield 'paid payment is preserved' => [OrderTransactionStates::STATE_PAID];
        yield 'refunded payment is preserved' => [OrderTransactionStates::STATE_REFUNDED];
        yield 'partially refunded payment is preserved' => [OrderTransactionStates::STATE_PARTIALLY_REFUNDED];
    }

    public function testCreatedOrderWithoutCancellationRequestRemainsUnchanged(): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_UNCONFIRMED);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->never())->method('isCancellationAllowed');
        $this->orderResource->expects($this->once())->method('get')->willReturn((new Order())->assign([
            'id' => 'paypal-order-id',
            'status' => 'CREATED',
            'purchase_units' => [['reference_id' => 'default']],
        ]));
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'paypal-order-id'));
    }

    public function testAuthorizedTransactionWithCancellationRequestStillReconciles(): void
    {
        $transaction = $this->createTransaction(OrderTransactionStates::STATE_AUTHORIZED);
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
        ]);
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));
        $this->orderExecuteService->expects($this->never())->method('isCancellationAllowed');
        $this->orderResource->expects($this->once())->method('get')->willReturn((new Order())->assign([
            'id' => 'paypal-order-id',
            'intent' => 'AUTHORIZE',
            'purchase_units' => [['payments' => ['authorizations' => [['id' => 'authorization-id', 'status' => 'CREATED']]]]],
        ]));
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());

        ($this->handler)(new TransactionStatusSyncMessage('transaction-id', 'sales-channel-id', 'paypal-order-id'));
    }

    public function testInvokeWithAuthorizedTransactionAndOrderWillSkip(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id']);
        $transaction->setStateMachineState(new StateMachineStateEntity());
        $this->orderTransactionRepository->addSearch(new OrderTransactionCollection([$transaction]));

        $payPalOrder = (new Order())->assign([
            'intent' => ConstantsV2::INTENT_AUTHORIZE,
            'purchaseUnits' => [[
                'payments' => [
                    'authorizations' => [['status' => ConstantsV2::INTENT_AUTHORIZE]],
                ],
            ]],
        ]);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($payPalOrder);

        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($payPalOrder, 'transaction-id');

        $this->orderTransactionStateHandler
            ->expects($this->never())
            ->method('authorize')
            ->with('transaction-id');

        $this->logger->expects($this->never())->method(static::anything());

        $message = new TransactionStatusSyncMessage(
            'transaction-id',
            'sales-channel-id',
            'paypal-order-id'
        );

        ($this->handler)($message);
    }

    private function createTransaction(string $state): OrderTransactionEntity
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id']);
        $transactionState = new StateMachineStateEntity();
        $transactionState->setTechnicalName($state);
        $transaction->setStateMachineState($transactionState);

        return $transaction;
    }
}
