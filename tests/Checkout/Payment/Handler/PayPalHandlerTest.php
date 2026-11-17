<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionDefinition;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\RecurringPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\OrderTransactionService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenDefinition;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
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
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\SwagPayPal;
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
        // e.g. headless checkouts, or the storefront without Smart Payment Buttons, pay with a saved PayPal account
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

    public function testFinalizeRenewalKeepsTheTokenOfTheSubscription(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());

        $struct = new RecurringPaymentTransactionStruct($orderTransaction, $order);
        $salesChannelContext = Generator::createSalesChannelContext();

        $payPalOrder = new Order();
        $payPalOrder->setPaymentSource(new PaymentSource());
        $payPalOrder->getPaymentSource()?->setPaypal(new Paypal());
        $payPalOrder->setId('paypalOrderId');

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('get')->willReturn($payPalOrder);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService->expects(static::once())->method('captureOrAuthorizeOrder')->willReturn($payPalOrder);

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService->expects(static::once())->method('saveToken');
        // a renewal keeps the token of its subscription, even if it was paid with another one
        $vaultTokenService->expects(static::never())->method('saveUsedTokenToSubscriptions');

        $handler = new PayPalHandler(
            $this->createMock(PayPalOrderBuilder::class),
            $orderResource,
            $orderExecuteService,
            $this->createMock(OrderPatchService::class),
            $this->createMock(TransactionDataService::class),
            $vaultTokenService,
            new NullLogger(),
        );

        $handler->handleFinalizeOrder($struct, 'paypalOrderId', 'salesChannelId', $salesChannelContext, PartnerAttributionId::PAYPAL_PPCP);
    }

    #[DataProvider('dataProviderPayPalOrderRecordsTheSavedToken')]
    public function testPayPalOrderRecordsTheSavedToken(RequestDataBag $requestDataBag, ?string $expectedTokenId): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $struct = new AsyncPaymentTransactionStruct($orderTransaction, new OrderEntity(), 'https://shop.test/payment/finalize-transaction?_sw_payment_token=token');
        $salesChannelContext = Generator::createSalesChannelContext();

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setId('vaultTokenId');

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService
            ->expects($expectedTokenId ? static::once() : static::never())
            ->method('getTokenForPayment')
            ->with($struct, $salesChannelContext->getContext(), false)
            ->willReturn($vaultToken);

        $transactionDataService = $this->createMock(TransactionDataService::class);
        // the order is paid with the saved token, which finalize() then saves to the subscription
        $transactionDataService
            ->expects(static::once())
            ->method('setVaultTokenId')
            ->with($orderTransaction, $expectedTokenId, $salesChannelContext->getContext());

        $response = $this->createPayPalOrderHandler($vaultTokenService, $transactionDataService)
            ->handlePayPalOrder($struct, $requestDataBag, $salesChannelContext);

        static::assertSame($struct->getReturnUrl() . '&token=paypalOrderId', $response->getTargetUrl());
    }

    public static function dataProviderPayPalOrderRecordsTheSavedToken(): \Generator
    {
        // e.g. headless checkouts, or the storefront without Smart Payment Buttons
        yield 'order with the saved token' => [new RequestDataBag(), 'vaultTokenId'];
        yield 'preliminary order without a saved token' => [new RequestDataBag([AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true]), null];
    }

    public function testPayPalOrderOfARenewalRecordsNoToken(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $struct = new RecurringPaymentTransactionStruct($orderTransaction, new OrderEntity());

        $vaultTokenService = $this->createMock(VaultTokenService::class);
        $vaultTokenService->expects(static::never())->method('getTokenForPayment');

        $transactionDataService = $this->createMock(TransactionDataService::class);
        $transactionDataService->expects(static::never())->method('setVaultTokenId');

        $response = $this->createPayPalOrderHandler($vaultTokenService, $transactionDataService)
            ->handlePayPalOrder($struct, new RequestDataBag(), Generator::createSalesChannelContext());

        static::assertSame('paypalOrderId', $response->getTargetUrl());
    }

    public function testPreparedOrderReplacesTheRecordedTokenWithTheVaultedOne(): void
    {
        if (!\class_exists(SubscriptionRecurringDataStruct::class)) {
            static::markTestSkipped('Commercial is not available');
        }

        // e.g. a payment with the saved PayPal account failed, and the payer approved another one in the same transaction
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $orderTransaction->setPaymentMethodId('payment-method-id');
        $orderTransaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_VAULT_TOKEN_ID => 'stale-token-id']);
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());

        $subscription = new SubscriptionEntity();
        $subscription->setId(Uuid::randomHex());
        $subscription->setNextSchedule(new \DateTime());

        $struct = new AsyncPaymentTransactionStruct($orderTransaction, $order, 'https://shop.test/payment/finalize-transaction?_sw_payment_token=token', new SubscriptionRecurringDataStruct($subscription));
        $salesChannelContext = Generator::createSalesChannelContext();

        $paypal = new Paypal();
        $paypal->setEmailAddress('payer@example.com');
        $paypal->setAttributes((new Attributes())->assign(['vault' => ['id' => 'new-vault-id', 'customer' => ['id' => 'paypal-customer-id']]]));
        $paymentSource = new PaymentSource();
        $paymentSource->setPaypal($paypal);
        $capturedOrder = new Order();
        $capturedOrder->setId('paypalOrderId');
        $capturedOrder->setIntent(PaymentIntentV2::CAPTURE);
        $capturedOrder->setPurchaseUnits(new PurchaseUnitCollection());
        $capturedOrder->setPaymentSource($paymentSource);

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('get')->willReturn($capturedOrder);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService->expects(static::once())->method('captureOrAuthorizeOrder')->willReturn($capturedOrder);

        $vaultTokenRepository = new StaticEntityRepository([[]], new VaultTokenDefinition());
        $subscriptionRepository = new StaticEntityRepository([], new SubscriptionDefinition());

        $handler = new PayPalHandler(
            $this->createMock(PayPalOrderBuilder::class),
            $orderResource,
            $orderExecuteService,
            $this->createMock(OrderPatchService::class),
            new TransactionDataService(
                $this->createMock(EntityRepository::class),
                $this->createMock(CredentialsUtilInterface::class),
                $this->createMock(OrderTransactionService::class),
            ),
            new VaultTokenService($vaultTokenRepository, new StaticEntityRepository([], new CustomerDefinition()), $subscriptionRepository),
            new NullLogger(),
        );

        $handler->handlePreparedOrder(
            $struct,
            new RequestDataBag([
                AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId',
                PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => true,
            ]),
            $salesChannelContext,
        );

        static::assertNull($orderTransaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_VAULT_TOKEN_ID));
        static::assertSame('new-vault-id', $vaultTokenRepository->upserts[0][0]['token'] ?? null);
        // only the newly vaulted PayPal account is saved to the subscription
        static::assertSame([[[
            'id' => $subscription->getId(),
            'customFields' => ['swagPaypalVaultToken_payment-method-id' => $vaultTokenRepository->upserts[0][0]['id']],
        ]]], $subscriptionRepository->upserts);
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

    private function createPayPalOrderHandler(VaultTokenService $vaultTokenService, TransactionDataService $transactionDataService): PayPalHandler
    {
        $builtOrder = new Order();
        $builtOrder->setPurchaseUnits(new PurchaseUnitCollection());

        $orderBuilder = $this->createMock(PayPalOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturn($builtOrder);

        // an order with a saved token needs no approval
        $response = new Order();
        $response->setId('paypalOrderId');
        $response->setStatus(PaymentStatusV2::ORDER_APPROVED);

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects(static::once())->method('create')->willReturn($response);

        return new PayPalHandler(
            $orderBuilder,
            $orderResource,
            $this->createMock(OrderExecuteService::class),
            $this->createMock(OrderPatchService::class),
            $transactionDataService,
            $vaultTokenService,
            new NullLogger(),
        );
    }
}
