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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineException;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Symfony\Component\HttpFoundation\Response;

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

    protected function setUp(): void
    {
        $this->orderResource = $this->createMock(OrderResource::class);
        $this->transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->orderExecuteService = new OrderExecuteService(
            $this->orderResource,
            $this->transactionStateHandler,
            static::createStub(OrderNumberPatchBuilder::class),
            $this->logger,
        );
    }

    public function testOrderGetOnMissingPayments(): void
    {
        $captureDataWithMissingPayment = CaptureOrderCapture::get();
        $captureDataWithMissingPayment['purchase_units'][0]['payments'] = null;

        $this->orderResource->expects($this->once())
            ->method('capture')
            ->willReturn((new Order())->assign($captureDataWithMissingPayment));

        $this->orderResource->expects($this->once())
            ->method('get')
            ->willReturn((new Order())->assign(GetCapturedOrderCapture::get()));

        $this->orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testCheckFinalizedStatusSetsInProgressOnPendingCapture(): void
    {
        $orderData = CaptureOrderCapture::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = ConstantsV2::ORDER_CAPTURE_PENDING;
        $order = (new Order())->assign($orderData);

        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->transactionStateHandler->expects($this->once())
            ->method('process')
            ->with($transactionId, $context);
        $this->transactionStateHandler->expects($this->never())->method('paid');
        $this->transactionStateHandler->expects($this->never())->method('reopen');

        $finalized = $this->orderExecuteService->checkFinalizedStatus($order, Uuid::randomHex(), $transactionId, $context, false);

        static::assertTrue($finalized);
    }

    public function testCheckFinalizedStatusSetsInProgressOnPendingAuthorization(): void
    {
        $orderData = AuthorizeOrderAuthorization::get();
        $orderData['purchase_units'][0]['payments']['authorizations'][0]['status'] = ConstantsV2::ORDER_AUTHORIZATION_PENDING;
        $order = (new Order())->assign($orderData);

        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->transactionStateHandler->expects($this->once())
            ->method('process')
            ->with($transactionId, $context);
        $this->transactionStateHandler->expects($this->never())->method('authorize');
        $this->transactionStateHandler->expects($this->never())->method('reopen');

        $finalized = $this->orderExecuteService->checkFinalizedStatus($order, Uuid::randomHex(), $transactionId, $context, false);

        static::assertTrue($finalized);
    }

    public function testCheckFinalizedStatusIgnoresIllegalTransitionOnPendingCapture(): void
    {
        $orderData = CaptureOrderCapture::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = ConstantsV2::ORDER_CAPTURE_PENDING;
        $order = (new Order())->assign($orderData);

        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        // the transaction is already "in progress", e.g. because the payment was executed before
        $this->transactionStateHandler->expects($this->once())
            ->method('process')
            ->with($transactionId, $context)
            ->willThrowException(StateMachineException::illegalStateTransition(
                OrderTransactionStates::STATE_IN_PROGRESS,
                StateMachineTransitionActions::ACTION_PROCESS,
                [],
            ));

        $finalized = $this->orderExecuteService->checkFinalizedStatus($order, Uuid::randomHex(), $transactionId, $context, false);

        static::assertTrue($finalized);
    }

    public function testCheckFinalizedStatusIgnoresIllegalTransitionOnPendingAuthorization(): void
    {
        $orderData = AuthorizeOrderAuthorization::get();
        $orderData['purchase_units'][0]['payments']['authorizations'][0]['status'] = ConstantsV2::ORDER_AUTHORIZATION_PENDING;
        $order = (new Order())->assign($orderData);

        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        // the transaction is already "in progress", e.g. because the payment was executed before
        $this->transactionStateHandler->expects($this->once())
            ->method('process')
            ->with($transactionId, $context)
            ->willThrowException(StateMachineException::illegalStateTransition(
                OrderTransactionStates::STATE_IN_PROGRESS,
                StateMachineTransitionActions::ACTION_PROCESS,
                [],
            ));

        $finalized = $this->orderExecuteService->checkFinalizedStatus($order, Uuid::randomHex(), $transactionId, $context, false);

        static::assertTrue($finalized);
    }

    public function testCheckFinalizedStatusDoesNotSwallowOtherStateMachineExceptions(): void
    {
        $orderData = CaptureOrderCapture::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = ConstantsV2::ORDER_CAPTURE_PENDING;
        $order = (new Order())->assign($orderData);

        $this->transactionStateHandler->expects($this->once())
            ->method('process')
            ->willThrowException(StateMachineException::stateMachineNotFound(OrderTransactionStates::STATE_MACHINE));

        $this->expectException(StateMachineException::class);
        $this->orderExecuteService->checkFinalizedStatus($order, Uuid::randomHex(), Uuid::randomHex(), Context::createDefaultContext(), false);
    }

    #[DataProvider('cancellationOrderStatusProvider')]
    public function testCancellationOnlyAllowedBeforeOrderApproval(string $status, bool $allowed): void
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

        static::assertSame($allowed, $this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id'));
    }

    public static function cancellationOrderStatusProvider(): \Generator
    {
        yield 'created order can still be cancelled' => ['CREATED', true];
        yield 'order awaiting payer action can still be cancelled' => ['PAYER_ACTION_REQUIRED', true];
        yield 'approved order may already be executing' => ['APPROVED', false];
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

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id'));
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

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id'));
    }

    /**
     * @param array<string, mixed> $orderData
     */
    #[DataProvider('ambiguousOrderProvider')]
    public function testCancellationDeniedForAmbiguousOrder(array $orderData): void
    {
        $this->orderResource->expects($this->once())->method('get')->willReturn((new Order())->assign($orderData));

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id'));
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

        static::assertFalse($this->orderExecuteService->isCancellationAllowed('paypal-order-id', 'sales-channel-id'));
    }

    public static function orderLookupFailureProvider(): \Generator
    {
        yield 'PayPal API is unavailable' => [new PayPalApiException('INTERNAL_SERVER_ERROR', 'PayPal is unavailable')];
        yield 'network connection fails' => [new ConnectException('Connection timed out', new Request('GET', 'https://api.paypal.com/v2/checkout/orders/paypal-order-id'))];
        yield 'malformed response cannot be hydrated' => [new \TypeError('Invalid PayPal response')];
    }

    public function testCapturePayerActionRequiredIsConverted(): void
    {
        $this->orderResource->expects($this->once())
            ->method('capture')
            ->willThrowException($this->createPayPalApiException(PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED));

        $this->expectExceptionObject(PayerActionRequiredException::payerActionRequired(CreateOrderCapture::ID));

        $this->orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    /**
     * The PayPal debug id only exists on the rejected response, so losing it leaves the payment log unattributable.
     */
    public function testCapturePayerActionRequiredKeepsTheRejectionAsPreviousException(): void
    {
        $rejection = $this->createPayPalApiException(PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED);

        $this->orderResource->expects($this->once())
            ->method('capture')
            ->willThrowException($rejection);

        try {
            $this->orderExecuteService->captureOrAuthorizeOrder(
                Uuid::randomHex(),
                (new Order())->assign(CreateOrderCapture::get()),
                Uuid::randomHex(),
                Context::createDefaultContext(),
                Uuid::randomHex(),
            );
        } catch (PayerActionRequiredException $e) {
            static::assertSame($rejection, $e->getPrevious());

            return;
        }

        static::fail('Expected a PayerActionRequiredException');
    }

    public function testPayerActionRequiredOrderStatusSkipsCapture(): void
    {
        $orderData = CreateOrderCapture::get();
        $orderData['status'] = ConstantsV2::ORDER_PAYER_ACTION_REQUIRED;

        $this->orderResource->expects($this->never())->method('capture');
        $this->orderResource->expects($this->never())->method('authorize');

        $this->expectException(PayerActionRequiredException::class);

        $this->orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testCaptureWithoutStatusStillCaptures(): void
    {
        $orderData = CreateOrderCapture::get();
        unset($orderData['status']);

        $this->orderResource->expects($this->once())
            ->method('capture')
            ->willReturn((new Order())->assign(CaptureOrderCapture::get()));

        $this->orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testUnrelatedUnprocessableEntityIsNotConverted(): void
    {
        $this->orderResource->expects($this->once())
            ->method('capture')
            ->willThrowException($this->createPayPalApiException('INSTRUMENT_DECLINED'));

        try {
            $this->orderExecuteService->captureOrAuthorizeOrder(
                Uuid::randomHex(),
                (new Order())->assign(CreateOrderCapture::get()),
                Uuid::randomHex(),
                Context::createDefaultContext(),
                Uuid::randomHex(),
            );
            static::fail('Expected a PayPalApiException to be thrown');
        } catch (PayPalApiException $e) {
            static::assertNotInstanceOf(PayerActionRequiredException::class, $e);
            static::assertSame('INSTRUMENT_DECLINED', $e->getIssue());
        }
    }

    private function createPayPalApiException(string $issue): PayPalApiException
    {
        return new PayPalApiException(
            ApiException::CODE_UNPROCESSABLE_ENTITY,
            'The requested action could not be performed, semantically incorrect, or failed business validation.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $issue,
        );
    }
}
