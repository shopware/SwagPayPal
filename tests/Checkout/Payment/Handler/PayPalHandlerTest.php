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
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Common\Link;
use Swag\PayPal\RestApi\V2\Api\Common\LinkCollection;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\Attributes;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Response;

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
        $payPalOrder->getPaymentSource()?->setPaypal(new Paypal());
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

        $handler = new PayPalHandler(
            $this->createMock(PayPalOrderBuilder::class),
            $orderResource,
            $orderExecuteService,
            $this->createMock(OrderPatchService::class),
            $transactionDataService,
            $vaultTokenService,
            new NullLogger(),
        );

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('Missing payment details for PayPal payment source');
        $handler->handleFinalizeOrder(
            $struct,
            'paypalOrderId',
            'salesChannelId',
            $salesChannelContext,
            'attributionId'
        );
    }

    public function testPreparedOrderPayerActionRequiredConfirmsWithoutVaultRequest(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        $struct = new AsyncPaymentTransactionStruct($orderTransaction, $order, 'https://shop.test/payment/finalize-transaction?_sw_payment_token=token');
        $salesChannelContext = Generator::createSalesChannelContext();
        $finalizeUrl = $struct->getReturnUrl() . '&token=paypalOrderId&isPayPalExpressCheckout=1';

        // the order builder requests the vaulting, e.g. for subscriptions or "save for later"
        $paypal = new Paypal();
        $paypal->setExperienceContext(new ExperienceContext());
        $paypal->setAttributes((new Attributes())->assign(['vault' => ['storeInVault' => 'ON_SUCCESS']]));
        $paymentSource = new PaymentSource();
        $paymentSource->setPaypal($paypal);
        $builtOrder = new Order();
        $builtOrder->setPaymentSource($paymentSource);

        $orderBuilder = $this->createMock(PayPalOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturn($builtOrder);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService
            ->method('captureOrAuthorizeOrder')
            ->willThrowException(new PayPalApiException('UNPROCESSABLE_ENTITY', 'Payer action required', Response::HTTP_UNPROCESSABLE_ENTITY, PayPalApiException::ISSUE_PAYER_ACTION_REQUIRED));

        $confirmedOrder = new Order();
        $confirmedOrder->setLinks(new LinkCollection([(new Link())->assign(['rel' => Link::RELATION_PAYER_ACTION, 'href' => 'https://paypal.test/payer-action'])]));

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('get')->willReturn(new Order());
        $orderResource
            ->expects(static::once())
            ->method('confirm')
            ->with(
                'paypalOrderId',
                static::callback(static function (PaymentSource $paymentSource) use ($finalizeUrl): bool {
                    $paypal = $paymentSource->getPaypal();
                    static::assertNotNull($paypal);
                    // the prepared order already requests the vaulting, asked again PayPal never vaults
                    static::assertNull($paypal->getAttributes());
                    static::assertSame(ExperienceContext::USER_ACTION_CONTINUE, $paypal->getExperienceContext()->getUserAction());
                    static::assertSame($finalizeUrl, $paypal->getExperienceContext()->getReturnUrl());

                    return true;
                }),
                $salesChannelContext->getSalesChannelId(),
                PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT,
            )
            ->willReturn($confirmedOrder);

        $handler = new PayPalHandler(
            $orderBuilder,
            $orderResource,
            $orderExecuteService,
            $this->createMock(OrderPatchService::class),
            $this->createMock(TransactionDataService::class),
            $this->createMock(VaultTokenService::class),
            new NullLogger(),
        );

        $response = $handler->handlePreparedOrder(
            $struct,
            new RequestDataBag([
                AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId',
                PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
                VaultTokenService::REQUEST_CREATE_VAULT => true,
            ]),
            $salesChannelContext,
        );

        static::assertSame('https://paypal.test/payer-action', $response->getTargetUrl());
    }
}
