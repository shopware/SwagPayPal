<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Handler;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalHandlerTest extends TestCase
{
    public function testFinalize(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        $struct = new SyncPaymentTransactionStruct(
            $orderTransaction,
            $order,
        );

        $salesChannelContext = Generator::createSalesChannelContext();

        $payPalOrder = new Order();
        $payPalOrder->setPaymentSource(new PaymentSource());
        $payPalOrder->getPaymentSource()?->setPaypal(new PayPal());
        $payPalOrder->setId('paypalOrderId');

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypalOrderId', 'salesChannelId')
            ->willReturn($payPalOrder);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService
            ->expects(static::once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $orderTransaction->getId(),
                $payPalOrder,
                'salesChannelId',
                $salesChannelContext->getContext(),
                'attributionId'
            )
            ->willReturn($payPalOrder);

        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($payPalOrder, $orderTransaction->getId(), $salesChannelContext->getContext());

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::once())
            ->method('saveToken')
            ->with(
                $struct,
                $payPalOrder->getPaymentSource()?->getPaypal(),
                $salesChannelContext->getCustomerId(),
                $salesChannelContext->getContext(),
            );

        // e.g. a checkout with the saved PayPal account, which PayPal returns no vault for
        $vaultTokenService
            ->expects(static::once())
            ->method('saveUsedTokenToSubscriptions')
            ->with($struct, $salesChannelContext->getContext());

        $handler = new PayPalHandler(
            $this->createMock(PayPalOrderBuilder::class),
            $orderResource,
            $orderExecuteService,
            $this->createMock(OrderPatchService::class),
            $transactionDataService,
            $vaultTokenService,
            new NullLogger(),
        );

        $handler->handleFinalizeOrder(
            $struct,
            'paypalOrderId',
            'salesChannelId',
            $salesChannelContext,
            'attributionId'
        );
    }

    public function testFinalizeWithOtherPaymentSource(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        $struct = new SyncPaymentTransactionStruct(
            $orderTransaction,
            $order,
        );

        $salesChannelContext = Generator::createSalesChannelContext();

        $payPalOrder = new Order();
        $payPalOrder->setPaymentSource(new PaymentSource());
        $payPalOrder->setId('paypalOrderId');

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypalOrderId', 'salesChannelId')
            ->willReturn($payPalOrder);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService
            ->expects(static::once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $orderTransaction->getId(),
                $payPalOrder,
                'salesChannelId',
                $salesChannelContext->getContext(),
                'attributionId'
            )
            ->willReturn($payPalOrder);

        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($payPalOrder, $orderTransaction->getId(), $salesChannelContext->getContext());

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('saveToken');
        $vaultTokenService
            ->expects(static::never())
            ->method('saveUsedTokenToSubscriptions');

        $handler = new PayPalHandler(
            $this->createMock(PayPalOrderBuilder::class),
            $orderResource,
            $orderExecuteService,
            $this->createMock(OrderPatchService::class),
            $transactionDataService,
            $vaultTokenService,
            new NullLogger(),
        );

        $this->expectException(AsyncPaymentFinalizeException::class);
        $this->expectExceptionMessage('Missing payment details for PayPal payment source');
        $handler->handleFinalizeOrder(
            $struct,
            'paypalOrderId',
            'salesChannelId',
            $salesChannelContext,
            'attributionId'
        );
    }

    public function testFinalizeRenewalKeepsTheTokenOfItsSubscription(): void
    {
        $struct = new RecurringPaymentTransactionStruct($this->createOrderTransaction(), new OrderEntity());
        $salesChannelContext = Generator::createSalesChannelContext();
        $payPalOrder = $this->createPayPalOrder();

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('get')->willReturn($payPalOrder);
        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService->method('captureOrAuthorizeOrder')->willReturn($payPalOrder);

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::once())
            ->method('saveToken');
        // a renewal keeps the token of its subscription, even if it was paid with another one
        $vaultTokenService
            ->expects(static::never())
            ->method('saveUsedTokenToSubscriptions');

        $this->createHandler(
            orderResource: $orderResource,
            orderExecuteService: $orderExecuteService,
            vaultTokenService: $vaultTokenService,
        )->handleFinalizeOrder($struct, 'paypalOrderId', 'salesChannelId', $salesChannelContext, 'attributionId');
    }

    public function testHandlePayPalOrderWithSavedAccountRecordsItsToken(): void
    {
        $struct = new AsyncPaymentTransactionStruct($this->createOrderTransaction(), new OrderEntity(), 'https://example.com/return?paymentToken=token');
        $salesChannelContext = Generator::createSalesChannelContext();

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setId('vaultTokenId');

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::once())
            ->method('getTokenForPayment')
            ->with($struct, $salesChannelContext->getContext(), false)
            ->willReturn($vaultToken);

        // finalize() saves it to the subscription, even if the customer saved another PayPal account meanwhile
        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService
            ->expects(static::once())
            ->method('setVaultTokenId')
            ->with($struct->getOrderTransaction(), 'vaultTokenId', $salesChannelContext->getContext());

        $response = $this->createHandler(
            orderBuilder: $this->createOrderBuilder('vaultToken'),
            orderResource: $this->createOrderResource(),
            transactionDataService: $transactionDataService,
            vaultTokenService: $vaultTokenService,
        )->handlePayPalOrder($struct, new RequestDataBag(), $salesChannelContext);

        // the saved PayPal account needs no approval
        static::assertSame('https://example.com/return?paymentToken=token&token=paypalOrderId', $response->getTargetUrl());
    }

    public function testHandlePayPalOrderWithoutSavedAccountRecordsNoToken(): void
    {
        $struct = new AsyncPaymentTransactionStruct($this->createOrderTransaction(), new OrderEntity(), 'https://example.com/return?paymentToken=token');
        $salesChannelContext = Generator::createSalesChannelContext();

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('getTokenForPayment');

        // the payer approves the order, so a token recorded by an earlier attempt is removed
        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService
            ->expects(static::once())
            ->method('setVaultTokenId')
            ->with($struct->getOrderTransaction(), null, $salesChannelContext->getContext());

        $this->createHandler(
            orderBuilder: $this->createOrderBuilder(null),
            orderResource: $this->createOrderResource(),
            transactionDataService: $transactionDataService,
            vaultTokenService: $vaultTokenService,
        )->handlePayPalOrder($struct, new RequestDataBag(), $salesChannelContext);
    }

    public function testHandlePayPalOrderOfARenewalRecordsNoToken(): void
    {
        $struct = new RecurringPaymentTransactionStruct($this->createOrderTransaction(), new OrderEntity());

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::never())
            ->method('getTokenForPayment');

        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService
            ->expects(static::never())
            ->method('setVaultTokenId');

        $response = $this->createHandler(
            orderBuilder: $this->createOrderBuilder('vaultToken'),
            orderResource: $this->createOrderResource(),
            transactionDataService: $transactionDataService,
            vaultTokenService: $vaultTokenService,
        )->handlePayPalOrder($struct, new RequestDataBag(), Generator::createSalesChannelContext());

        static::assertSame('paypalOrderId', $response->getTargetUrl());
    }

    public function testHandlePreparedOrderRecordsNoTokenBeforeTheCapture(): void
    {
        $struct = new AsyncPaymentTransactionStruct($this->createOrderTransaction(), new OrderEntity(), 'https://example.com/return?paymentToken=token');
        $salesChannelContext = Generator::createSalesChannelContext();
        $payPalOrder = $this->createPayPalOrder();
        $calls = [];

        // the payer approved the prepared order, so a token recorded by an earlier attempt must not be saved to the subscription
        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService
            ->expects(static::once())
            ->method('setVaultTokenId')
            ->with($struct->getOrderTransaction(), null, $salesChannelContext->getContext())
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'setVaultTokenId';
            });

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('get')->willReturn($payPalOrder);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService
            ->expects(static::once())
            ->method('captureOrAuthorizeOrder')
            ->willReturnCallback(static function () use (&$calls, $payPalOrder): Order {
                $calls[] = 'capture';

                return $payPalOrder;
            });

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects(static::once())
            ->method('saveUsedTokenToSubscriptions')
            ->with($struct, $salesChannelContext->getContext())
            ->willReturnCallback(static function () use (&$calls): void {
                $calls[] = 'saveUsedTokenToSubscriptions';
            });

        $this->createHandler(
            orderResource: $orderResource,
            orderExecuteService: $orderExecuteService,
            transactionDataService: $transactionDataService,
            vaultTokenService: $vaultTokenService,
        )->handlePreparedOrder($struct, new RequestDataBag([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']), $salesChannelContext);

        static::assertSame(['setVaultTokenId', 'capture', 'saveUsedTokenToSubscriptions'], $calls);
    }

    private function createHandler(
        ?PayPalOrderBuilder $orderBuilder = null,
        ?OrderResource $orderResource = null,
        ?OrderExecuteService $orderExecuteService = null,
        ?TransactionDataService $transactionDataService = null,
        ?VaultTokenService $vaultTokenService = null,
    ): PayPalHandler {
        return new PayPalHandler(
            $orderBuilder ?? $this->createMock(PayPalOrderBuilder::class),
            $orderResource ?? $this->createMock(OrderResource::class),
            $orderExecuteService ?? $this->createMock(OrderExecuteService::class),
            $this->createMock(OrderPatchService::class),
            $transactionDataService ?? $this->createMock(TransactionDataService::class),
            $vaultTokenService ?? $this->createMock(VaultTokenService::class),
            new NullLogger(),
        );
    }

    private function createOrderBuilder(?string $vaultId): PayPalOrderBuilder
    {
        $paypal = new Paypal();
        if ($vaultId !== null) {
            $paypal->setVaultId($vaultId);
        }
        $paymentSource = new PaymentSource();
        $paymentSource->setPaypal($paypal);
        $paypalOrder = new Order();
        $paypalOrder->setPaymentSource($paymentSource);

        $orderBuilder = $this->createMock(PayPalOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturn($paypalOrder);

        return $orderBuilder;
    }

    private function createOrderResource(): OrderResource
    {
        $orderResource = $this->createMock(OrderResource::class);
        // like the real resource, which assigns PayPal's response to the given order, replacing its payment source without the vault id
        $orderResource->method('create')->willReturnCallback(static fn (Order $order): Order => $order->assign([
            'id' => 'paypalOrderId',
            'status' => PaymentStatusV2::ORDER_APPROVED,
            'payment_source' => ['paypal' => ['email_address' => 'payer@example.com']],
        ]));

        return $orderResource;
    }

    private function createOrderTransaction(): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());

        return $orderTransaction;
    }

    private function createPayPalOrder(): Order
    {
        $payPalOrder = new Order();
        $payPalOrder->setId('paypalOrderId');
        $payPalOrder->setPaymentSource(new PaymentSource());
        $payPalOrder->getPaymentSource()?->setPaypal(new Paypal());

        return $payPalOrder;
    }
}
