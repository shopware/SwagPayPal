<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\Method\APMHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderExecuteService::class)]
class OrderExecuteServiceTest extends TestCase
{
    private OrderResource&MockObject $orderResource;

    private OrderTransactionStateHandler&MockObject $transactionStateHandler;

    private LoggerInterface&MockObject $logger;

    private OrderExecuteService $orderExecuteService;

    private OrderTransactionEntity $transaction;

    /**
     * @var StaticEntityRepository<OrderTransactionCollection>
     */
    private StaticEntityRepository $transactionRepository;

    private LockFactory $lockFactory;

    private Context $context;

    protected function setUp(): void
    {
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = Context::createDefaultContext();
        $this->transaction = new OrderTransactionEntity();
        $this->transaction->setId('order-transaction-id');
        $state = new StateMachineStateEntity();
        $state->setTechnicalName(OrderTransactionStates::STATE_OPEN);
        $this->transaction->setStateMachineState($state);
        $this->transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
        ]);
        $this->transactionRepository = new StaticEntityRepository([new OrderTransactionCollection([$this->transaction])]);
        $this->lockFactory = new LockFactory(new InMemoryStore());
        $this->orderExecuteService = new OrderExecuteService(
            $this->orderResource,
            $this->transactionStateHandler,
            static::createStub(OrderNumberPatchBuilder::class),
            $this->logger,
            $this->transactionRepository,
            $this->lockFactory,
        );
    }

    public function testOrderGetOnMissingPayments(): void
    {
        $this->transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => CreateOrderCapture::ID,
        ]);
        $captureDataWithMissingPayment = CaptureOrderCapture::get();
        $captureDataWithMissingPayment['purchase_units'][0]['payments'] = null;

        $this->orderResource->expects($this->once())
            ->method('capture')
            ->willReturn((new Order())->assign($captureDataWithMissingPayment));

        $this->orderResource->expects($this->once())
            ->method('get')
            ->willReturn((new Order())->assign(GetCapturedOrderCapture::get()));

        $this->orderExecuteService->captureOrAuthorizeOrder(
            $this->transaction->getId(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            $this->context,
            Uuid::randomHex(),
        );
    }

    #[DataProvider('cancellationOrderStatusProvider')]
    public function testCancellationRequiresVerifiedPrePaymentStatus(string $status, bool $allowed): void
    {
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'status' => $status,
            'purchase_units' => [['reference_id' => 'default']],
        ]);
        $this->orderResource->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willReturn($order);
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');
        $this->transactionStateHandler->expects($this->never())->method('paid');
        $this->transactionStateHandler->expects($this->never())->method('authorize');

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function cancellationOrderStatusProvider(): \Generator
    {
        yield 'created order can still be cancelled' => ['CREATED', true];
        yield 'order awaiting payer action can still be cancelled' => ['PAYER_ACTION_REQUIRED', true];
        yield 'approved order without a known execution mode remains ambiguous' => ['APPROVED', false];
        yield 'completed order must be reconciled' => ['COMPLETED', false];
        yield 'saved order remains in progress' => ['SAVED', false];
        yield 'pending approval is not a confirmed pre-payment state' => ['PENDING_APPROVAL', false];
        yield 'voided order is owned by provider reconciliation' => ['VOIDED', false];
        yield 'unknown future status preserves the transaction' => ['UNKNOWN', false];
    }

    /**
     * @param array<string, list<array{id: string, status?: string}>> $payments
     */
    #[DataProvider('existingPaymentResourceProvider')]
    public function testCancellationDeniedWhenPaymentResourcesExist(array $payments): void
    {
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'status' => 'CREATED',
            'purchase_units' => [['payments' => $payments]],
        ]);
        $this->orderResource->expects($this->once())->method('get')->willReturn($order);
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');
        $this->transactionStateHandler->expects($this->never())->method('paid');
        $this->transactionStateHandler->expects($this->never())->method('authorize');

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function existingPaymentResourceProvider(): \Generator
    {
        yield 'pending capture can settle later' => [['captures' => [['id' => 'capture-id', 'status' => 'PENDING']]]];
        yield 'completed capture is already paid' => [['captures' => [['id' => 'capture-id', 'status' => 'COMPLETED']]]];
        yield 'partial refund must remain reconciled' => [['captures' => [['id' => 'capture-id', 'status' => 'PARTIALLY_REFUNDED']]]];
        yield 'failed capture is still owned by provider reconciliation' => [['captures' => [['id' => 'capture-id', 'status' => 'FAILED']]]];
        yield 'pending authorization can complete later' => [['authorizations' => [['id' => 'authorization-id', 'status' => 'PENDING']]]];
        yield 'created authorization has reserved funds' => [['authorizations' => [['id' => 'authorization-id', 'status' => 'CREATED']]]];
        yield 'partially captured authorization must remain reconciled' => [['authorizations' => [['id' => 'authorization-id', 'status' => 'PARTIALLY_CAPTURED']]]];
        yield 'voided authorization is still owned by provider reconciliation' => [['authorizations' => [['id' => 'authorization-id', 'status' => 'VOIDED']]]];
        yield 'refund proves payment has already started' => [['refunds' => [['id' => 'refund-id', 'status' => 'COMPLETED']]]];
        yield 'payment with missing status preserves the transaction' => [['captures' => [['id' => 'capture-id']]]];
    }

    public function testCancellationChecksEveryPurchaseUnit(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'status' => 'CREATED',
            'purchase_units' => [
                ['reference_id' => 'unpaid-unit'],
                ['reference_id' => 'paid-unit', 'payments' => ['captures' => [['id' => 'capture-id', 'status' => 'PENDING']]]],
            ],
        ]);
        $this->orderResource->expects($this->once())->method('get')->willReturn($order);

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    /**
     * @param array<string, mixed> $orderData
     */
    #[DataProvider('ambiguousOrderProvider')]
    public function testCancellationDeniedForAmbiguousOrder(array $orderData): void
    {
        $this->orderResource->expects($this->once())->method('get')->willReturn((new Order())->assign($orderData));

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function ambiguousOrderProvider(): \Generator
    {
        yield 'missing order identifier cannot confirm the payment' => [['status' => 'CREATED', 'purchase_units' => [[]]]];
        yield 'different order cannot confirm this payment' => [['id' => 'other-order-id', 'status' => 'CREATED', 'purchase_units' => [[]]]];
        yield 'missing status preserves the transaction' => [['id' => 'paypal-order-id', 'purchase_units' => [[]]]];
        yield 'missing purchase units cannot prove there are no payments' => [['id' => 'paypal-order-id', 'status' => 'CREATED']];
        yield 'empty purchase units cannot prove there are no payments' => [['id' => 'paypal-order-id', 'status' => 'CREATED', 'purchase_units' => []]];
    }

    #[DataProvider('orderLookupFailureProvider')]
    public function testCancellationDeniedAndLoggedWhenOrderLookupFails(\Throwable $exception): void
    {
        $this->orderResource->expects($this->once())
            ->method('get')
            ->with('paypal-order-id', 'sales-channel-id')
            ->willThrowException($exception);
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');
        $this->transactionStateHandler->expects($this->never())->method('paid');
        $this->transactionStateHandler->expects($this->never())->method('authorize');
        $this->logger->expects($this->once())->method('warning');

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
        $lock = $this->lockFactory->createLock('swag-paypal-order-paypal-order-id');
        static::assertTrue($lock->acquire());
        $lock->release();
    }

    public static function orderLookupFailureProvider(): \Generator
    {
        yield 'PayPal API is unavailable' => [new PayPalApiException('INTERNAL_SERVER_ERROR', 'PayPal is unavailable')];
        yield 'network connection fails' => [new ConnectException('Connection timed out', new Request('GET', 'https://api.paypal.com/v2/checkout/orders/paypal-order-id'))];
        yield 'malformed response cannot be hydrated' => [new \TypeError('Invalid PayPal response')];
    }

    /**
     * @param array<string, mixed> $executionData
     */
    #[DataProvider('approvedOrderExecutionModeProvider')]
    public function testApprovedCancellationRequiresManualExecution(array $executionData, bool $allowed): void
    {
        $order = (new Order())->assign($executionData + [
            'id' => 'paypal-order-id',
            'status' => 'APPROVED',
            'purchase_units' => [['reference_id' => 'default']],
        ]);
        $this->orderResource->expects($this->once())->method('get')->willReturn($order);
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function approvedOrderExecutionModeProvider(): \Generator
    {
        yield 'explicit manual processing is safe before execution' => [['processing_instruction' => 'NO_INSTRUCTION'], true];
        yield 'PayPal checkout awaits server execution' => [['payment_source' => ['paypal' => ['email_address' => 'customer@example.com']]], true];
        yield 'card checkout awaits server execution' => [['payment_source' => ['card' => ['last_digits' => '1234']]], true];
        yield 'Apple Pay checkout awaits server execution' => [['payment_source' => ['apple_pay' => ['card' => ['last_digits' => '1234']]]], true];
        yield 'Google Pay checkout awaits server execution' => [['payment_source' => ['google_pay' => ['card' => ['last_digits' => '1234']]]], true];
        yield 'Venmo checkout awaits server execution' => [['payment_source' => ['venmo' => ['user_name' => 'customer']]], true];
        yield 'automatic processing overrides a known manual source' => [['processing_instruction' => 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL', 'payment_source' => ['paypal' => ['email_address' => 'customer@example.com']]], false];
        yield 'unknown processing instruction overrides a known manual source' => [['processing_instruction' => 'UNKNOWN', 'payment_source' => ['card' => ['last_digits' => '1234']]], false];
        yield 'unknown source cannot establish manual execution' => [['payment_source' => ['sofort' => ['name' => 'Test User']]], false];
        yield 'empty payment source cannot establish manual execution' => [['payment_source' => []], false];
    }

    #[DataProvider('missingPaymentSourceHandlerProvider')]
    public function testMissingPaymentSourceRequiresKnownManualPaymentHandler(string $handler, ?string $processingInstruction, bool $allowed): void
    {
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setHandlerIdentifier($handler);
        $this->transaction->setPaymentMethod($paymentMethod);
        $orderData = GetOrderCapture::get();
        $orderData['id'] = 'paypal-order-id';
        unset($orderData['payment_source']);
        if ($processingInstruction !== null) {
            $orderData['processing_instruction'] = $processingInstruction;
        }

        $this->orderResource->expects($this->once())->method('get')->willReturn((new Order())->assign($orderData));
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function missingPaymentSourceHandlerProvider(): \Generator
    {
        yield 'wallet handler establishes manual execution when the source is missing' => [PayPalPaymentHandler::class, null, true];
        yield 'alternative payment handler cannot establish manual execution' => [APMHandler::class, null, false];
        yield 'automatic processing overrides the wallet handler' => [PayPalPaymentHandler::class, 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL', false];
        yield 'unknown processing overrides the wallet handler' => [PayPalPaymentHandler::class, 'UNKNOWN', false];
    }

    #[DataProvider('currentTransactionStateProvider')]
    public function testCancellationUsesCurrentTransactionState(string $state, bool $allowed): void
    {
        $currentState = $this->transaction->getStateMachineState();
        static::assertNotNull($currentState);
        $currentState->setTechnicalName($state);
        $this->orderResource->expects($allowed ? $this->once() : $this->never())
            ->method('get')
            ->willReturn((new Order())->assign([
                'id' => 'paypal-order-id',
                'status' => 'CREATED',
                'purchase_units' => [['reference_id' => 'default']],
            ]));

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function currentTransactionStateProvider(): \Generator
    {
        yield 'open transaction can be cancelled' => [OrderTransactionStates::STATE_OPEN, true];
        yield 'unconfirmed transaction without payment can be cancelled' => [OrderTransactionStates::STATE_UNCONFIRMED, true];
        yield 'in-progress transaction without payment can be cancelled' => [OrderTransactionStates::STATE_IN_PROGRESS, true];
        yield 'paid transaction remains paid' => [OrderTransactionStates::STATE_PAID, false];
        yield 'authorized transaction remains authorized' => [OrderTransactionStates::STATE_AUTHORIZED, false];
        yield 'partially paid transaction preserves settlement' => [OrderTransactionStates::STATE_PARTIALLY_PAID, false];
        yield 'refunded transaction remains refunded' => [OrderTransactionStates::STATE_REFUNDED, false];
        yield 'partially refunded transaction preserves settlement' => [OrderTransactionStates::STATE_PARTIALLY_REFUNDED, false];
        yield 'cancelled transaction is not cancelled again' => [OrderTransactionStates::STATE_CANCELLED, false];
        yield 'unknown transaction state is preserved' => ['unknown', false];
    }

    #[DataProvider('cancellationMarkerProvider')]
    public function testCancellationRequestMustBelongToCurrentPayPalOrder(?string $orderId, ?string $cancellationOrderId, bool $allowed): void
    {
        $this->transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $orderId,
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => $cancellationOrderId,
        ]);
        $this->orderResource->expects($allowed ? $this->once() : $this->never())
            ->method('get')
            ->willReturn((new Order())->assign([
                'id' => 'paypal-order-id',
                'status' => 'CREATED',
                'purchase_units' => [['reference_id' => 'default']],
            ]));

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function cancellationMarkerProvider(): \Generator
    {
        yield 'current cancellation request can be checked' => ['paypal-order-id', 'paypal-order-id', true];
        yield 'missing order id cannot identify this payment' => [null, 'paypal-order-id', false];
        yield 'newer order supersedes a stale cancellation' => ['newer-order-id', 'paypal-order-id', false];
        yield 'missing cancellation request must not cancel a transaction' => ['paypal-order-id', null, false];
        yield 'another order cancellation must not apply' => ['paypal-order-id', 'previous-order-id', false];
    }

    #[DataProvider('executionMarkerProvider')]
    public function testExecutionMarkerProtectsPaymentBeforeResourcesAppear(string $status, string $executionOrderId, bool $allowed): void
    {
        $this->transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED => $executionOrderId,
        ]);
        $this->orderResource->method('get')->willReturn((new Order())->assign([
            'id' => 'paypal-order-id',
            'status' => $status,
            'processing_instruction' => 'NO_INSTRUCTION',
            'purchase_units' => [['reference_id' => 'default']],
        ]));
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public static function executionMarkerProvider(): \Generator
    {
        yield 'capture timeout can leave remote order created' => ['CREATED', 'paypal-order-id', false];
        yield 'started payment may still request payer action' => ['PAYER_ACTION_REQUIRED', 'paypal-order-id', false];
        yield 'capture timeout can leave remote order approved' => ['APPROVED', 'paypal-order-id', false];
        yield 'previous order execution does not block current cancellation' => ['APPROVED', 'previous-order-id', true];
    }

    public function testMissingTransactionCannotBeCancelled(): void
    {
        $this->orderExecuteService = new OrderExecuteService(
            $this->orderResource,
            $this->transactionStateHandler,
            static::createStub(OrderNumberPatchBuilder::class),
            $this->logger,
            new StaticEntityRepository([new OrderTransactionCollection()]),
            $this->lockFactory,
        );
        $this->orderResource->expects($this->never())->method('get');

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
    }

    public function testCancellationDoesNotWaitForExecutingOrder(): void
    {
        $executionLock = $this->lockFactory->createLock('swag-paypal-order-paypal-order-id');
        static::assertTrue($executionLock->acquire());
        $this->orderResource->expects($this->never())->method('get');

        try {
            static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
        } finally {
            $executionLock->release();
        }
    }

    public function testCancellationCanRecoverAfterFailedLookupWithoutExecutingPayment(): void
    {
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'intent' => 'CAPTURE',
            'status' => 'APPROVED',
            'payment_source' => ['paypal' => ['email_address' => 'customer@example.com']],
            'purchase_units' => [['reference_id' => 'default']],
        ]);
        $this->transactionRepository->addSearch(
            new OrderTransactionCollection([$this->transaction]),
            new OrderTransactionCollection([$this->transaction]),
        );
        $lookups = 0;
        $this->orderResource->expects($this->exactly(2))->method('get')->willReturnCallback(static function () use (&$lookups, $order): Order {
            if (++$lookups === 1) {
                throw new PayPalApiException('INTERNAL_SERVER_ERROR', 'PayPal is unavailable');
            }

            return $order;
        });
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');
        $this->transactionStateHandler->expects($this->never())->method('paid');
        $this->transactionStateHandler->expects($this->never())->method('authorize');
        $this->logger->expects($this->once())->method('warning');

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
        static::assertSame($order, $this->orderExecuteService->captureOrAuthorizeOrder($this->transaction->getId(), $order, 'sales-channel-id', $this->context, 'partner-id'));
        static::assertTrue($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id', $this->transaction->getId(), $this->context));
        static::assertSame([], $this->transactionRepository->updates);
    }

    #[DataProvider('executionIntentProvider')]
    public function testCancellationRequestSuppressesPaymentExecution(string $intent, string $resourceMethod): void
    {
        $order = (new Order())->assign(['id' => 'paypal-order-id', 'intent' => $intent]);
        $this->orderResource->expects($this->never())->method($resourceMethod);
        $this->orderResource->expects($this->never())->method('get');
        $this->transactionStateHandler->expects($this->never())->method('paid');
        $this->transactionStateHandler->expects($this->never())->method('authorize');

        static::assertSame($order, $this->orderExecuteService->captureOrAuthorizeOrder($this->transaction->getId(), $order, 'sales-channel-id', $this->context, 'partner-id'));
        static::assertSame([], $this->transactionRepository->updates);
    }

    #[DataProvider('settledPaymentProvider')]
    public function testCancellationRequestStillReconcilesAlreadySettledPayment(string $intent, string $paymentType, string $status, string $stateMethod): void
    {
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'intent' => $intent,
            'purchase_units' => [['payments' => [$paymentType => [['id' => 'payment-resource-id', 'status' => $status]]]]],
        ]);
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');
        $this->orderResource->expects($this->never())->method('get');
        $this->transactionStateHandler->expects($this->once())->method($stateMethod)
            ->with($this->transaction->getId(), $this->context);

        static::assertSame($order, $this->orderExecuteService->captureOrAuthorizeOrder($this->transaction->getId(), $order, 'sales-channel-id', $this->context, 'partner-id'));
        static::assertSame([], $this->transactionRepository->updates);
    }

    public static function settledPaymentProvider(): \Generator
    {
        yield 'completed capture still marks the transaction paid' => ['CAPTURE', 'captures', 'COMPLETED', 'paid'];
        yield 'created authorization still marks the transaction authorized' => ['AUTHORIZE', 'authorizations', 'CREATED', 'authorize'];
    }

    #[DataProvider('executionIntentProvider')]
    public function testExecutionProofIsPersistedBeforeProviderRequest(string $intent, string $resourceMethod): void
    {
        $this->transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_CANCELLATION_REQUESTED => 'previous-order-id',
            'unrelated_field' => 'preserved',
        ]);
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'intent' => $intent,
            'purchase_units' => [['payments' => []]],
        ]);
        $this->orderResource->expects($this->once())->method($resourceMethod)
            ->willReturnCallback(function () use ($order): Order {
                static::assertCount(1, $this->transactionRepository->updates);
                $update = $this->transactionRepository->updates[0][0];
                static::assertSame($this->transaction->getId(), $update['id']);
                static::assertSame('paypal-order-id', $update['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED]);

                $competingLock = $this->lockFactory->createLock('swag-paypal-order-paypal-order-id');
                static::assertFalse($competingLock->acquire());

                return $order;
            });

        static::assertSame($order, $this->orderExecuteService->captureOrAuthorizeOrder($this->transaction->getId(), $order, 'sales-channel-id', $this->context, 'partner-id'));
        $lock = $this->lockFactory->createLock('swag-paypal-order-paypal-order-id');
        static::assertTrue($lock->acquire());
        $lock->release();
    }

    public static function executionIntentProvider(): \Generator
    {
        yield 'capture' => ['CAPTURE', 'capture'];
        yield 'authorization' => ['AUTHORIZE', 'authorize'];
    }

    public function testExecutionTimeoutRetainsProofAndReleasesLock(): void
    {
        $this->transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'paypal-order-id',
        ]);
        $order = (new Order())->assign([
            'id' => 'paypal-order-id',
            'intent' => 'CAPTURE',
            'purchase_units' => [['payments' => []]],
        ]);
        $exception = new ConnectException('Connection timed out', new Request('POST', 'https://api.paypal.com/v2/checkout/orders/paypal-order-id/capture'));
        $this->orderResource->expects($this->once())->method('capture')->willThrowException($exception);
        $this->expectExceptionObject($exception);

        try {
            $this->orderExecuteService->captureOrAuthorizeOrder($this->transaction->getId(), $order, 'sales-channel-id', $this->context, 'partner-id');
        } finally {
            static::assertCount(1, $this->transactionRepository->updates);
            static::assertSame('paypal-order-id', $this->transactionRepository->updates[0][0]['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_EXECUTION_STARTED]);
            $lock = $this->lockFactory->createLock('swag-paypal-order-paypal-order-id');
            static::assertTrue($lock->acquire());
            $lock->release();
        }
    }
}
