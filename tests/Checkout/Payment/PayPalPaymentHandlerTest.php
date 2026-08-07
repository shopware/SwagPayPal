<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\PatchCollection;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\PaymentResumeService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder as OrderNumberPatchBuilderV2;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;
use Swag\PayPal\Test\Mock\PayPalSDK\MockRequestHandler;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalPaymentHandlerTest extends TestCase
{
    use GatewayTestBehaviour;
    use IntegrationTestBehaviour;
    use OrderTransactionTrait;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    public const PAYER_ID_PAYMENT_INCOMPLETE = 'testPayerIdIncomplete';
    public const PAYER_ID_DUPLICATE_TRANSACTION = 'testPayerIdDuplicateTransaction';
    public const PAYPAL_PATCH_THROWS_EXCEPTION = 'invalidId';
    public const PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER = 'paypalOrderIdDuplicateOrderNumber';
    public const PAYPAL_ORDER_ID_INSTRUMENT_DECLINED = 'paypalOrderIdInstrumentDeclined';
    private const RETURN_URL = 'https://example.com/payment/finalize-transaction?_sw_payment_token=testToken';
    private const TEST_CUSTOMER_STREET = 'Street 1';
    private const TEST_CUSTOMER_FIRST_NAME = 'FirstName';
    private const TEST_CUSTOMER_LAST_NAME = 'LastName';
    private const TEST_AMOUNT = '20028.00';
    private const TEST_SHIPPING = '10.00';

    private EntityRepository $orderTransactionRepo;

    private StateMachineRegistry $stateMachineRegistry;

    protected function setUp(): void
    {
        $this->orderTransactionRepo = $this->getContainer()->get(OrderTransactionDefinition::ENTITY_NAME . '.repository');
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
    }

    public function testPay(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);
        $response = $handler->pay(new Request(), $paymentTransaction, Context::createDefaultContext(), null);

        static::assertSame(CreateOrderCapture::APPROVE_URL, $response?->getTargetUrl());
        static::assertSame(
            CreateOrderCapture::ID,
            $this->getTransaction($transactionId, $this->getContainer(), Context::createDefaultContext())?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID)
        );

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, Context::createDefaultContext());
    }

    public function testPayWithEcs(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);
        $paypalOrderId = GetOrderCapture::ID;

        $response = $handler->pay(new Request([], [
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId,
        ]), $paymentTransaction, Context::createDefaultContext(), null);

        static::assertNull($response);
        static::assertSame(
            $paypalOrderId,
            $this->getTransaction($transactionId, $this->getContainer(), Context::createDefaultContext())?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID)
        );

        $body = self::getClient()->getLastWhere(static fn ($context) => $context->getRequest()->getMethod() === 'PATCH')?->getRequestBody();
        static::assertIsArray($body);
        $patches = PatchCollection::createFromAssociative($body);
        static::assertCount(1, $patches);
        $patch = $patches->getAt(0);
        static::assertNotNull($patch);
        static::assertSame('/purchase_units/@reference_id==\'default\'', $patch->getPath());
        $patchValue = $patch->getValue();
        static::assertIsArray($patchValue);
        static::assertSame(self::TEST_CUSTOMER_STREET, $patchValue['shipping']['address']['address_line_1']);
        static::assertSame(\sprintf('%s %s', self::TEST_CUSTOMER_FIRST_NAME, self::TEST_CUSTOMER_LAST_NAME), $patchValue['shipping']['name']['full_name']);
        static::assertSame(self::TEST_AMOUNT, $patchValue['amount']['value']);
        static::assertSame(self::TEST_SHIPPING, $patchValue['amount']['breakdown']['shipping']['value']);
        static::assertSame(1, $patchValue['items'][0]['quantity']);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PAID, $transactionId, Context::createDefaultContext());
    }

    public function testPayWithEcsPayerActionRequiredRedirectsToRenewedApproval(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);
        $salesChannelContext = $this->createStorefrontSalesChannelContext();

        $request = $this->createStorefrontRequest([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $salesChannelContext);

        $response = $handler->pay($request, $paymentTransaction, Context::createDefaultContext(), null);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame(MockRequestHandler::PAYER_ACTION_URL, $response->getTargetUrl());

        $orderTransaction = $this->getTransaction($transactionId, $this->getContainer(), Context::createDefaultContext());
        static::assertNotNull($orderTransaction);
        static::assertSame(
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            $orderTransaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID)
        );

        static::assertSame(self::RETURN_URL, $this->createPaymentResumeService()->consume(
            $request->getSession(),
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ));

        // no confirm-payment-source (voids the renewed approval) and no new order (orphans the approved one)
        $uris = \array_map(
            static fn ($context) => $context->getRequest()->getMethod() . ' ' . $context->getRequest()->getUri(),
            self::getClient()->getAll(),
        );
        static::assertNull($this->indexOfRequestEndingWith($uris, '/confirm-payment-source'));
        static::assertNull($this->indexOfRequestEndingWith($uris, '/v2/checkout/orders'));

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, Context::createDefaultContext());
    }

    /**
     * Smart Payment Buttons approve a preliminary order and patch it before capture just like Express does.
     */
    public function testPayWithSpbPayerActionRequiredRedirectsToRenewedApproval(): void
    {
        $handler = $this->createPayPalPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);

        $request = $this->createStorefrontRequest([
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $this->createStorefrontSalesChannelContext());

        $response = $handler->pay($request, $paymentTransaction, Context::createDefaultContext(), null);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame(MockRequestHandler::PAYER_ACTION_URL, $response->getTargetUrl());
        static::assertSame(self::RETURN_URL, $this->createPaymentResumeService()->consume(
            $request->getSession(),
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ));

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, Context::createDefaultContext());
    }

    public function testPayWithEcsPayerActionRequiredWithoutReturnUrlFails(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(PayerActionRequiredException::class);

        $handler->pay($this->createStorefrontRequest([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $this->createStorefrontSalesChannelContext()), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithEcsPayerActionRequiredWithoutSalesChannelContextFails(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);

        $this->expectException(PayerActionRequiredException::class);

        $request = $this->createStorefrontRequest([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $this->createStorefrontSalesChannelContext());
        $request->attributes->remove(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        $handler->pay($request, $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithEcsPayerActionRequiredWithoutStorefrontScopeFails(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);

        $this->expectException(PayerActionRequiredException::class);

        $request = $this->createStorefrontRequest([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $this->createStorefrontSalesChannelContext());
        $request->attributes->remove(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE);

        $handler->pay($request, $paymentTransaction, Context::createDefaultContext(), null);
    }

    /**
     * A second checkout tab creates its own PayPal order, whose resume must survive alongside this one.
     */
    public function testPayWithEcsPayerActionRequiredKeepsTheResumeOfAnotherPayPalOrder(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);
        $resumeService = $this->createPaymentResumeService();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);
        $salesChannelContext = $this->createStorefrontSalesChannelContext();

        $request = $this->createStorefrontRequest([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $salesChannelContext);

        $session = $request->getSession();
        $resumeService->store($session, 'otherTabPayPalOrderId', 'https://example.test/other-tab', $salesChannelContext->getSalesChannelId());

        $response = $handler->pay($request, $paymentTransaction, Context::createDefaultContext(), null);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame(self::RETURN_URL, $resumeService->consume($session, MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED));
        static::assertSame('https://example.test/other-tab', $resumeService->consume($session, 'otherTabPayPalOrderId'));
    }

    /**
     * Without a session there is nowhere to remember the resume for the returning payer.
     */
    public function testPayWithEcsPayerActionRequiredWithoutSessionFails(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);

        $this->expectException(PayerActionRequiredException::class);

        $handler->pay($this->createStorefrontRequest([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ], $this->createStorefrontSalesChannelContext(), false), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testFinalizePayerActionRequiredIsNotRecovered(): void
    {
        // must never redirect again, a payer could be bounced between shop and PayPal
        $this->expectException(PayerActionRequiredException::class);

        $this->assertFinalizeRequest(MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED);
    }

    /**
     * Order::$links has no default and PayPal may omit it, which must not raise an uninitialized property error.
     */
    public function testResolveRedirectToleratesOrdersWithoutLinks(): void
    {
        $handler = new class($this->createMock(SettingsValidationServiceInterface::class), $this->createMock(StateMachineRegistry::class), $this->createMock(OrderExecuteService::class), $this->createMock(OrderPatchService::class), $this->createMock(TransactionDataService::class), $this->createMock(OrderResource::class), $this->createMock(VaultTokenService::class), $this->createMock(EntityRepository::class), $this->createMock(AbstractOrderBuilder::class), $this->createMock(PaymentResumeService::class)) extends PayPalPaymentHandler {
            public function resolveRedirectOf(?Order $order): ?string
            {
                return $this->resolveRedirect($order);
            }
        };

        static::assertNull($handler->resolveRedirectOf(null));
        static::assertNull($handler->resolveRedirectOf(new Order()));

        $approved = (new Order())->assign(['links' => [['rel' => Link::RELATION_APPROVE, 'href' => 'https://paypal.test/approve']]]);
        static::assertSame('https://paypal.test/approve', $handler->resolveRedirectOf($approved));

        $actionRequired = (new Order())->assign(['links' => [['rel' => Link::RELATION_PAYER_ACTION, 'href' => 'https://paypal.test/payer-action']]]);
        static::assertSame('https://paypal.test/payer-action', $handler->resolveRedirectOf($actionRequired));
    }

    public function testPayWithEcsThrowsException(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The error "TEST" occurred with the following message: generalClientExceptionMessage', '/') . '\z/');
        $handler->pay(new Request([], [
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_PATCH_THROWS_EXCEPTION,
        ]), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testPayWithSpb(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);
        $paypalOrderId = GetOrderCapture::ID;

        $response = $handler->pay(
            new Request([], [
                AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId,
            ]),
            $paymentTransaction,
            Context::createDefaultContext(),
            null
        );

        static::assertNull($response);
        static::assertSame(
            $paypalOrderId,
            $this->getTransaction($transactionId, $this->getContainer(), Context::createDefaultContext())?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID)
        );

        $body = self::getClient()->getLastWhere(static fn ($context) => $context->getRequest()->getMethod() === 'PATCH')?->getRequestBody();
        static::assertIsArray($body);
        $patches = PatchCollection::createFromAssociative($body);
        static::assertCount(1, $patches);
        $patch = $patches->getAt(0);
        static::assertNotNull($patch);
        static::assertSame('/purchase_units/@reference_id==\'default\'', $patch->getPath());
        $patchValue = $patch->getValue();
        static::assertIsArray($patchValue);
        static::assertSame(self::TEST_CUSTOMER_STREET, $patchValue['shipping']['address']['address_line_1']);
        static::assertSame(\sprintf('%s %s', self::TEST_CUSTOMER_FIRST_NAME, self::TEST_CUSTOMER_LAST_NAME), $patchValue['shipping']['name']['full_name']);
        static::assertSame(self::TEST_AMOUNT, $patchValue['amount']['value']);
        static::assertSame(self::TEST_SHIPPING, $patchValue['amount']['breakdown']['shipping']['value']);
        static::assertSame(1, $patchValue['items'][0]['quantity']);
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $handler = $this->createPayPalPaymentHandler();
        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(PayPalSettingsInvalidException::class);
        $handler->pay(new Request(), $paymentTransaction, Context::createDefaultContext(), null);
    }

    public function testFinalizeWithCancel(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The customer canceled the external payment process. Customer canceled the payment on the PayPal page', '/') . '\z/');
        $this->createPayPalPaymentHandler()->finalize(
            new Request([PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_CANCEL => true]),
            new PaymentTransactionStruct($this->getTransactionId(Context::createDefaultContext(), $this->getContainer())),
            Context::createDefaultContext(),
        );
    }

    public function testFinalizePayPalOrderCapture(): void
    {
        $this->assertFinalizeRequest(GetOrderCapture::ID, OrderTransactionStates::STATE_PAID, CaptureOrderCapture::CAPTURE_ID);
    }

    public function testFinalizePayPalOrderAuthorize(): void
    {
        $this->assertFinalizeRequest(GetOrderAuthorization::ID, OrderTransactionStates::STATE_AUTHORIZED, GetAuthorization::ID);
    }

    public function testFinalizePayPalOrderCaptureWithException(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The error "UNPROCESSABLE_ENTITY" occurred with the following message: The requested action could not be completed, was semantically incorrect, or failed business validation. | [INSTRUMENT_DECLINED] The instrument presented was either declined by the processor or bank, or it can\'t be used for this payment.', '/') . '\z/');

        $this->assertFinalizeRequest(self::PAYPAL_ORDER_ID_INSTRUMENT_DECLINED);
    }

    public function testFinalizePayPalOrderPatchOrderNumber(): void
    {
        $this->assertFinalizeRequest(GetOrderCapture::ID, OrderTransactionStates::STATE_PAID, CaptureOrderCapture::CAPTURE_ID);
    }

    public function testFinalizePayPalOrderPatchOrderNumberDuplicate(): void
    {
        CaptureOrderCapture::setDuplicateOrderNumber(true);
        $this->assertFinalizeRequest(self::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER, OrderTransactionStates::STATE_PAID, CaptureOrderCapture::CAPTURE_ID);
    }

    /**
     * @param array<int, string> $uris
     */
    private function indexOfRequestEndingWith(array $uris, string $suffix): ?int
    {
        foreach ($uris as $index => $uri) {
            if (\str_ends_with($uri, $suffix)) {
                return $index;
            }
        }

        return null;
    }

    private function createStorefrontRequest(array $body, SalesChannelContext $salesChannelContext, bool $withSession = true): Request
    {
        $request = new Request([], $body, [
            PlatformRequest::ATTRIBUTE_ROUTE_SCOPE => [StorefrontRouteScope::ID],
            PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT => $salesChannelContext,
        ]);

        if ($withSession) {
            $request->setSession(new Session(new MockArraySessionStorage()));
        }

        return $request;
    }

    private function createPayPalPaymentHandler(array $settings = []): PayPalPaymentHandler
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $orderResource = new OrderResource(self::orderGateway(), new ApiContextFactoryMock());
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $logger = new NullLogger();

        return new PayPalPaymentHandler(
            new SettingsValidationService($systemConfig, new NullLogger()),
            $this->stateMachineRegistry,
            new OrderExecuteService(
                $orderResource,
                $orderTransactionStateHandler,
                new OrderNumberPatchBuilderV2(),
                $logger
            ),
            new OrderPatchService(
                $systemConfig,
                new PurchaseUnitPatchBuilder(
                    new PurchaseUnitProvider(
                        new AmountProvider(new PriceFormatter()),
                        new AddressProvider(),
                        new CustomIdProviderMock(),
                        $systemConfig
                    ),
                    new ItemListProvider(
                        new PriceFormatter(),
                        $this->createMock(EventDispatcherInterface::class),
                        new NullLogger(),
                    ),
                ),
                $orderResource,
            ),
            new TransactionDataService(
                $this->orderTransactionRepo,
                new CredentialsUtil($systemConfig),
            ),
            $orderResource,
            $this->createMock(VaultTokenService::class),
            $this->orderTransactionRepo,
            $this->createOrderBuilder($systemConfig),
            $this->createPaymentResumeService(),
        );
    }

    /**
     * The service is stateless and container-inlined, so a local instance reads the same session.
     */
    private function createPaymentResumeService(): PaymentResumeService
    {
        return new PaymentResumeService(
            $this->getContainer()->get(SystemConfigService::class),
            new NativeClock(),
        );
    }

    private function createStorefrontSalesChannelContext(): SalesChannelContext
    {
        return $this->getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
    }

    private function assertFinalizeRequest(
        string $paypalOrderId,
        string $state = OrderTransactionStates::STATE_PAID,
        ?string $resourceId = null,
    ): string {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $context = Context::createDefaultContext();

        $transactionId = $this->getTransactionId($context, $this->getContainer());
        $this->getContainer()->get(OrderTransactionDefinition::ENTITY_NAME . '.repository')->update([[
            'id' => $transactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $paypalOrderId,
            ],
        ]], $context);

        $handler->finalize(
            new Request(),
            new PaymentTransactionStruct($transactionId),
            $context,
        );

        $this->assertOrderTransactionState($state, $transactionId, $context);

        if ($resourceId) {
            static::assertSame($resourceId, $this->getTransaction($transactionId, $this->getContainer(), $context)?->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID));
        }

        return $transactionId;
    }
}
