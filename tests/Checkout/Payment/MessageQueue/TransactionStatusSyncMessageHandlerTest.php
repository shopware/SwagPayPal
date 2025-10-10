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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\StateMachineException;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessage;
use Swag\PayPal\Checkout\Payment\MessageQueue\TransactionStatusSyncMessageHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TransactionStatusSyncMessageHandler::class)]
class TransactionStatusSyncMessageHandlerTest extends TestCase
{
    private EntityRepository&MockObject $orderTransactionRepository;

    private OrderTransactionStateHandler&MockObject $orderTransactionStateHandler;

    private OrderResource&MockObject $orderResource;

    private TransactionDataService&MockObject $transactionDataService;

    private LoggerInterface&MockObject $logger;

    private TransactionStatusSyncMessageHandler $handler;

    protected function setUp(): void
    {
        $this->orderTransactionRepository = $this->createMock(EntityRepository::class);
        $this->orderTransactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->transactionDataService = $this->createMock(TransactionDataService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new TransactionStatusSyncMessageHandler(
            $this->orderTransactionRepository,
            $this->orderTransactionStateHandler,
            $this->orderResource,
            $this->transactionDataService,
            $this->logger,
        );
    }

    #[DataProvider('dataProviderInvokeWithAllMatchingStatus')]
    public function testInvokeWithAllMatchingStatus(string $intent, string $status, ?string $stateHandlerMethod): void
    {
        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('search')
            ->willReturnCallback(
                function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

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
        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('search')
            ->willReturnCallback(
                function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

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
        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('search')
            ->willReturnCallback(
                function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

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
        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('search')
            ->willReturnCallback(
                function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

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
        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('search')
            ->willReturnCallback(
                fn (Criteria $criteria, Context $context) => new EntitySearchResult('order_transaction', 0, new EntityCollection(), null, $criteria, $context)
            );

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
        $this->orderTransactionRepository->expects($this->never())->method(static::anything());
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

    public function testInvokeWithAuthorizedTransactionAndOrderWillSkip(): void
    {
        $this->orderTransactionRepository
            ->expects($this->once())
            ->method('search')
            ->willReturnCallback(
                function (Criteria $criteria, Context $context): EntitySearchResult {
                    $orderTransactionEntity = new OrderTransactionEntity();
                    $orderTransactionEntity->setId('test-id');
                    $orderTransactionEntity->setStateMachineState(new StateMachineStateEntity());

                    return new EntitySearchResult('order_transaction', 1, new EntityCollection([$orderTransactionEntity]), null, $criteria, $context);
                }
            );

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
}
