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
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
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
                $salesChannelContext
            );

        $handler = new PayPalHandler(
            $this->createMock(OrderFromOrderBuilder::class),
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

        $handler = new PayPalHandler(
            $this->createMock(OrderFromOrderBuilder::class),
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
}
