<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\Method\APMHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderAPM;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderAPM;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(APMHandler::class)]
class APMHandlerTest extends TestCase
{
    private const TRANSACTION_ID = '019aa5d0b8f47c3e9c1b2d6e4f8a0b1c';

    private OrderResource&MockObject $orderResource;

    private OrderTransactionStateHandler&MockObject $orderTransactionStateHandler;

    private TransactionDataService&MockObject $transactionDataService;

    private APMHandler $handler;

    protected function setUp(): void
    {
        $order = new OrderEntity();
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(self::TRANSACTION_ID);
        $orderTransaction->setOrder($order);
        $orderTransaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => GetOrderAPM::ID]);

        $this->orderResource = $this->createMock(OrderResource::class);
        $this->orderTransactionStateHandler = $this->createMock(OrderTransactionStateHandler::class);
        $this->transactionDataService = $this->createMock(TransactionDataService::class);

        $this->handler = new APMHandler(
            static::createStub(SettingsValidationServiceInterface::class),
            static::createStub(StateMachineRegistry::class),
            new OrderExecuteService(
                $this->orderResource,
                $this->orderTransactionStateHandler,
                static::createStub(OrderNumberPatchBuilder::class),
                new NullLogger(),
            ),
            static::createStub(OrderPatchService::class),
            $this->transactionDataService,
            $this->orderResource,
            static::createStub(VaultTokenService::class),
            new StaticEntityRepository([new OrderTransactionCollection([$orderTransaction])]),
            static::createStub(AbstractOrderBuilder::class),
        );
    }

    public function testFinalizeSetsCompletedCaptureToPaid(): void
    {
        $context = Context::createDefaultContext();
        $paypalOrder = $this->createCapturedOrder(ConstantsV2::ORDER_CAPTURE_COMPLETED);

        $this->orderResource
            ->expects($this->once())
            ->method('get')
            ->with(GetOrderAPM::ID, TestDefaults::SALES_CHANNEL)
            ->willReturn($paypalOrder);
        // PayPal captures the order on approval
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderTransactionStateHandler
            ->expects($this->once())
            ->method('paid')
            ->with(self::TRANSACTION_ID, $context);
        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($paypalOrder, self::TRANSACTION_ID, $context);

        $this->handler->finalize(new Request(), new PaymentTransactionStruct(self::TRANSACTION_ID), $context);
    }

    public function testFinalizeSetsPendingCaptureInProgress(): void
    {
        $context = Context::createDefaultContext();
        $paypalOrder = $this->createCapturedOrder(ConstantsV2::ORDER_CAPTURE_PENDING);

        $this->orderResource->expects($this->once())->method('get')->willReturn($paypalOrder);
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderTransactionStateHandler
            ->expects($this->once())
            ->method('process')
            ->with(self::TRANSACTION_ID, $context);
        $this->orderTransactionStateHandler->expects($this->never())->method('paid');
        $this->transactionDataService
            ->expects($this->once())
            ->method('setResourceId')
            ->with($paypalOrder, self::TRANSACTION_ID, $context);

        $this->handler->finalize(new Request(), new PaymentTransactionStruct(self::TRANSACTION_ID), $context);
    }

    #[DataProvider('dataProviderUncapturedOrderStatus')]
    public function testFinalizeKeepsStateWithoutCapture(string $orderStatus): void
    {
        $orderData = GetOrderAPM::get();
        $orderData['status'] = $orderStatus;

        // the webhook or the transaction status sync sets the state, once PayPal captured the order
        $this->orderResource->expects($this->once())->method('get')->willReturn((new Order())->assign($orderData));
        $this->orderResource->expects($this->never())->method('capture');
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());

        $this->handler->finalize(new Request(), new PaymentTransactionStruct(self::TRANSACTION_ID), Context::createDefaultContext());
    }

    public static function dataProviderUncapturedOrderStatus(): \Generator
    {
        yield 'approved' => [ConstantsV2::ORDER_APPROVED];
        yield 'payer action required' => [ConstantsV2::ORDER_PAYER_ACTION_REQUIRED];
    }

    #[DataProvider('dataProviderFailedCaptureStatus')]
    public function testFinalizeFailsOnFailedCapture(string $captureStatus): void
    {
        $this->orderResource->expects($this->once())->method('get')->willReturn($this->createCapturedOrder($captureStatus));
        $this->orderTransactionStateHandler->expects($this->never())->method(static::anything());
        $this->transactionDataService->expects($this->never())->method('setResourceId');

        $this->expectException(OrderFailedException::class);

        $this->handler->finalize(new Request(), new PaymentTransactionStruct(self::TRANSACTION_ID), Context::createDefaultContext());
    }

    public static function dataProviderFailedCaptureStatus(): \Generator
    {
        yield 'declined' => [ConstantsV2::ORDER_CAPTURE_DECLINED];
        yield 'failed' => [ConstantsV2::ORDER_CAPTURE_FAILED];
    }

    private function createCapturedOrder(string $captureStatus): Order
    {
        $orderData = CaptureOrderAPM::get();
        $orderData['purchase_units'][0]['payments']['captures'][0]['status'] = $captureStatus;

        return (new Order())->assign($orderData);
    }
}
