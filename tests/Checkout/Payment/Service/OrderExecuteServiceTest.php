<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\AuthorizeOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;

/**
 * @internal
 */
#[Package('checkout')]
class OrderExecuteServiceTest extends TestCase
{
    public function testOrderGetOnMissingPayments(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = new OrderExecuteService(
            $orderResource,
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(OrderNumberPatchBuilder::class),
            new NullLogger(),
        );

        $captureDataWithMissingPayment = CaptureOrderCapture::get();
        $captureDataWithMissingPayment['purchase_units'][0]['payments'] = null;

        $orderResource->expects(static::once())
            ->method('capture')
            ->willReturn((new Order())->assign($captureDataWithMissingPayment));

        $orderResource->expects(static::once())
            ->method('get')
            ->willReturn((new Order())->assign(GetCapturedOrderCapture::get()));

        $orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testPendingCaptureSetsInProgress(): void
    {
        $orderData = CaptureOrderCapture::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = PaymentStatusV2::ORDER_CAPTURE_PENDING;

        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects(static::never())->method('capture');
        $orderResource->expects(static::never())->method('authorize');

        $transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $transactionStateHandler->expects(static::once())
            ->method('process')
            ->with($transactionId, $context);
        $transactionStateHandler->expects(static::never())->method('paid');

        $this->createOrderExecuteService($orderResource, $transactionStateHandler)->captureOrAuthorizeOrder(
            $transactionId,
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            $context,
            Uuid::randomHex(),
        );
    }

    public function testPendingAuthorizationSetsInProgress(): void
    {
        $orderData = AuthorizeOrderAuthorization::get();
        $orderData['purchase_units'][0]['payments']['authorizations'][0]['status'] = PaymentStatusV2::ORDER_AUTHORIZATION_PENDING;

        $transactionId = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects(static::never())->method('capture');
        $orderResource->expects(static::never())->method('authorize');

        $transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $transactionStateHandler->expects(static::once())
            ->method('process')
            ->with($transactionId, $context);
        $transactionStateHandler->expects(static::never())->method('authorize');

        $this->createOrderExecuteService($orderResource, $transactionStateHandler)->captureOrAuthorizeOrder(
            $transactionId,
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            $context,
            Uuid::randomHex(),
        );
    }

    public function testPendingCaptureIgnoresIllegalTransition(): void
    {
        $orderData = CaptureOrderCapture::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = PaymentStatusV2::ORDER_CAPTURE_PENDING;

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects(static::never())->method('capture');

        // the transaction is already "in progress", e.g. because the payment was executed before
        $transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $transactionStateHandler->expects(static::once())
            ->method('process')
            ->willThrowException(StateMachineException::illegalStateTransition(
                OrderTransactionStates::STATE_IN_PROGRESS,
                StateMachineTransitionActions::ACTION_PROCESS,
                [],
            ));

        $this->createOrderExecuteService($orderResource, $transactionStateHandler)->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testPendingAuthorizationIgnoresIllegalTransition(): void
    {
        $orderData = AuthorizeOrderAuthorization::get();
        $orderData['purchase_units'][0]['payments']['authorizations'][0]['status'] = PaymentStatusV2::ORDER_AUTHORIZATION_PENDING;

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects(static::never())->method('authorize');

        // the transaction is already "in progress", e.g. because the payment was executed before
        $transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $transactionStateHandler->expects(static::once())
            ->method('process')
            ->willThrowException(StateMachineException::illegalStateTransition(
                OrderTransactionStates::STATE_IN_PROGRESS,
                StateMachineTransitionActions::ACTION_PROCESS,
                [],
            ));

        $this->createOrderExecuteService($orderResource, $transactionStateHandler)->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testPendingCaptureDoesNotSwallowOtherStateMachineExceptions(): void
    {
        $orderData = CaptureOrderCapture::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = PaymentStatusV2::ORDER_CAPTURE_PENDING;

        $transactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $transactionStateHandler->expects(static::once())
            ->method('process')
            ->willThrowException(StateMachineException::stateMachineNotFound(OrderTransactionStates::STATE_MACHINE));

        $this->expectException(StateMachineException::class);
        $this->createOrderExecuteService($this->createMock(OrderResource::class), $transactionStateHandler)->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    private function createOrderExecuteService(OrderResource $orderResource, OrderTransactionStateHandler $transactionStateHandler): OrderExecuteService
    {
        return new OrderExecuteService(
            $orderResource,
            $transactionStateHandler,
            $this->createMock(OrderNumberPatchBuilder::class),
            new NullLogger(),
        );
    }
}
