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
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\V2\Common\Link;
use Shopware\PayPalSDK\Struct\V2\Common\LinkCollection;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Common\Attributes\Vault;
use Shopware\PayPalSDK\Struct\V2\PatchCollection;
use Shopware\PayPalSDK\Test\Request\TestRequestContext;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
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
use Swag\PayPal\Setting\Settings;
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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    private const MOBILE_USER_AGENT = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) Mobile/15E148 Safari/604.1';
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

    public function testPayWithEcsPayerActionRequiredConfirmsPaymentSourceAndRedirects(): void
    {
        $handler = $this->createPayPalPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);

        $response = $handler->pay(new Request([], [
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
        ]), $paymentTransaction, Context::createDefaultContext(), null);

        static::assertInstanceOf(RedirectResponse::class, $response);
        // the approval the confirmed payment source reopened, not the one of the rejected capture
        static::assertSame(MockRequestHandler::CONFIRMED_PAYER_ACTION_URL, $response->getTargetUrl());

        $confirmations = $this->getGatewayRequests('confirmPaymentSource');
        static::assertCount(1, $confirmations);
        static::assertStringEndsWith(
            \sprintf('/%s/confirm-payment-source', MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED),
            (string) $confirmations[0]->getRequest()->getUri(),
        );

        $paypalSource = $confirmations[0]->getRequestBody()['payment_source']['paypal'] ?? null;
        static::assertIsArray($paypalSource);
        // the payer returns into finalize, which resumes the payment from its own token
        static::assertSame(self::RETURN_URL, $paypalSource['experience_context']['return_url'] ?? null);
        static::assertSame(self::RETURN_URL . '&cancel=1', $paypalSource['experience_context']['cancel_url'] ?? null);

        // no new PayPal order, which would orphan the one the payer approved
        static::assertCount(0, $this->getGatewayRequests('createOrder'));

        $orderTransaction = $this->getTransaction($transactionId, $this->getContainer(), Context::createDefaultContext());
        static::assertNotNull($orderTransaction);
        static::assertSame(
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            $orderTransaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID)
        );

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, Context::createDefaultContext());
    }

    /**
     * App Switch returns report the approval in the URL fragment, which never reaches the shop,
     * so the renewed approval must not switch into the PayPal app again.
     */
    public function testPayWithSpbPayerActionRequiredConfirmsWithoutAppSwitchContext(): void
    {
        $settings = $this->getDefaultConfigData();
        $settings[Settings::SPB_APP_SWITCH_ENABLED] = true;
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId, self::RETURN_URL);

        $payload = [
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            'product' => 'spb',
            CreateOrderRoute::PAYPAL_BUYER_USER_AGENT => self::MOBILE_USER_AGENT,
        ];

        // the very same payload builds an App Switch order {@see PayPalOrderBuilderTest::testGetOrderAddsAppSwitchContext}
        $response = $handler->pay(new Request([], $payload), $paymentTransaction, Context::createDefaultContext(), null);

        static::assertInstanceOf(RedirectResponse::class, $response);
        static::assertSame(MockRequestHandler::CONFIRMED_PAYER_ACTION_URL, $response->getTargetUrl());

        $confirmations = $this->getGatewayRequests('confirmPaymentSource');
        static::assertCount(1, $confirmations);
        $paypalSource = $confirmations[0]->getRequestBody()['payment_source']['paypal'] ?? null;
        static::assertIsArray($paypalSource);
        static::assertNull($paypalSource['experience_context']['app_switch_context'] ?? null);
        // an existing vault token would capture without any payer, so the renewed consent must be interactive
        static::assertArrayNotHasKey('vault_id', $paypalSource);
        static::assertSame(self::RETURN_URL, $paypalSource['experience_context']['return_url'] ?? null);
        // nothing was ticked, so nothing is vaulted
        static::assertArrayNotHasKey('vault', $paypalSource['attributes'] ?? []);
    }

    public function testPayerActionRecoveryKeepsTheVaultingThePayerAskedFor(): void
    {
        $confirmationRequest = null;
        $orderBuilder = $this->createMock(AbstractOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturnCallback(
            static function (
                PaymentTransactionStruct $transaction,
                OrderTransactionEntity $orderTransaction,
                OrderEntity $order,
                Context $context,
                Request $request,
            ) use (&$confirmationRequest): Order {
                $confirmationRequest = $request;

                $paypalOrder = new Order();
                $paypalOrder->setPaymentSource(new PaymentSource());

                return $paypalOrder;
            }
        );

        $confirmed = new Order();
        $link = new Link();
        $link->setRel(Link::RELATION_PAYER_ACTION);
        $link->setHref(MockRequestHandler::CONFIRMED_PAYER_ACTION_URL);
        $confirmed->setLinks(new LinkCollection([$link]));

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('confirm')->willReturn($confirmed);

        $handler = new class(...$this->createHandlerDependencies($orderResource, $orderBuilder)) extends PayPalPaymentHandler {
            public function recoverFromPayerActionOf(
                PayerActionRequiredException $exception,
                Request $request,
                string $preparedOrderId,
                PaymentTransactionStruct $transaction,
                OrderTransactionEntity $orderTransaction,
                OrderEntity $order,
                Context $context,
            ): RedirectResponse {
                return $this->recoverFromPayerAction($exception, $request, $preparedOrderId, $transaction, $orderTransaction, $order, $context);
            }
        };

        $payerRequest = new Request([], [
            'product' => 'spb',
            CreateOrderRoute::PAYPAL_BUYER_USER_AGENT => self::MOBILE_USER_AGENT,
            VaultTokenService::REQUEST_CREATE_VAULT => true,
        ]);

        $handler->recoverFromPayerActionOf(
            PayerActionRequiredException::payerActionRequired(MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED),
            $payerRequest,
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            new PaymentTransactionStruct('orderTransactionId', self::RETURN_URL),
            new OrderTransactionEntity(),
            $this->createOrderWithSalesChannel(),
            Context::createDefaultContext(),
        );

        static::assertInstanceOf(Request::class, $confirmationRequest);
        // the payer ticked "save for later" before the capture was rejected; re-approving must carry
        // that consent, or the order is captured and the payment method never vaulted
        static::assertTrue($confirmationRequest->request->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT));
        // ... while everything that would make the return silent or non-interactive stays behind
        static::assertTrue($confirmationRequest->attributes->getBoolean(AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE));
        static::assertSame('', $confirmationRequest->request->getString('product'));
        static::assertSame('', $confirmationRequest->request->getString(CreateOrderRoute::PAYPAL_BUYER_USER_AGENT));
    }

    public function testPayerActionRecoveryDoesNotInventVaultingThePayerDidNotAskFor(): void
    {
        $confirmationRequest = null;
        $orderBuilder = $this->createMock(AbstractOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturnCallback(
            static function (
                PaymentTransactionStruct $transaction,
                OrderTransactionEntity $orderTransaction,
                OrderEntity $order,
                Context $context,
                Request $request,
            ) use (&$confirmationRequest): Order {
                $confirmationRequest = $request;

                $paypalOrder = new Order();
                $paypalOrder->setPaymentSource(new PaymentSource());

                return $paypalOrder;
            }
        );

        $confirmed = new Order();
        $link = new Link();
        $link->setRel(Link::RELATION_PAYER_ACTION);
        $link->setHref(MockRequestHandler::CONFIRMED_PAYER_ACTION_URL);
        $confirmed->setLinks(new LinkCollection([$link]));

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->method('confirm')->willReturn($confirmed);

        $handler = new class(...$this->createHandlerDependencies($orderResource, $orderBuilder)) extends PayPalPaymentHandler {
            public function recoverFromPayerActionOf(
                PayerActionRequiredException $exception,
                Request $request,
                string $preparedOrderId,
                PaymentTransactionStruct $transaction,
                OrderTransactionEntity $orderTransaction,
                OrderEntity $order,
                Context $context,
            ): RedirectResponse {
                return $this->recoverFromPayerAction($exception, $request, $preparedOrderId, $transaction, $orderTransaction, $order, $context);
            }
        };

        $handler->recoverFromPayerActionOf(
            PayerActionRequiredException::payerActionRequired(MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED),
            new Request([], ['product' => 'spb']),
            MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            new PaymentTransactionStruct('orderTransactionId', self::RETURN_URL),
            new OrderTransactionEntity(),
            $this->createOrderWithSalesChannel(),
            Context::createDefaultContext(),
        );

        static::assertInstanceOf(Request::class, $confirmationRequest);
        static::assertFalse($confirmationRequest->request->getBoolean(VaultTokenService::REQUEST_CREATE_VAULT));
    }

    public function testPayWithEcsPayerActionRequiredWithoutReturnUrlFails(): void
    {
        $handler = $this->createPayPalPaymentHandler($this->getDefaultConfigData());

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        try {
            $handler->pay(new Request([], [
                PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
                AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED,
            ]), $paymentTransaction, Context::createDefaultContext(), null);
        } catch (PayerActionRequiredException $e) {
            // without a return URL the renewed approval could not lead back into the shop
            static::assertCount(0, $this->getGatewayRequests('confirmPaymentSource'));

            return;
        }

        static::fail(\sprintf('Expected a %s', PayerActionRequiredException::class));
    }

    /**
     * A PayPal order the shop created itself was never approved, so there is no approval to renew.
     */
    public function testPayerActionRecoveryRequiresAPreparedOrder(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderResource->expects($this->never())->method('confirm');

        $handler = new class(...$this->createHandlerDependencies($orderResource)) extends PayPalPaymentHandler {
            public function recoverFromPayerActionOf(
                PayerActionRequiredException $exception,
                Request $request,
                string $preparedOrderId,
                PaymentTransactionStruct $transaction,
                OrderTransactionEntity $orderTransaction,
                OrderEntity $order,
                Context $context,
            ): RedirectResponse {
                return $this->recoverFromPayerAction($exception, $request, $preparedOrderId, $transaction, $orderTransaction, $order, $context);
            }
        };

        $this->expectException(PayerActionRequiredException::class);

        $handler->recoverFromPayerActionOf(
            PayerActionRequiredException::payerActionRequired(MockRequestHandler::PAYPAL_ORDER_ID_PAYER_ACTION_REQUIRED),
            new Request(),
            '',
            new PaymentTransactionStruct('orderTransactionId', self::RETURN_URL),
            new OrderTransactionEntity(),
            new OrderEntity(),
            Context::createDefaultContext(),
        );
    }

    /**
     * Order::$links has no default and PayPal may omit it, which must not raise an uninitialized property error.
     */
    public function testResolveRedirectToleratesOrdersWithoutLinks(): void
    {
        $handler = new class(...$this->createHandlerDependencies()) extends PayPalPaymentHandler {
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

    private function createOrderWithSalesChannel(): OrderEntity
    {
        $order = new OrderEntity();
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);

        return $order;
    }

    /**
     * @return array<int, mixed>
     */
    private function createHandlerDependencies(
        ?OrderResource $orderResource = null,
        ?AbstractOrderBuilder $orderBuilder = null,
    ): array {
        return [
            $this->createMock(SettingsValidationServiceInterface::class),
            $this->createMock(StateMachineRegistry::class),
            $this->createMock(OrderExecuteService::class),
            $this->createMock(OrderPatchService::class),
            $this->createMock(TransactionDataService::class),
            $orderResource ?? $this->createMock(OrderResource::class),
            $this->createMock(VaultTokenService::class),
            $this->createMock(EntityRepository::class),
            $orderBuilder ?? $this->createMock(AbstractOrderBuilder::class),
        ];
    }

    /**
     * @return list<TestRequestContext>
     */
    private function getGatewayRequests(string $gatewayMethod): array
    {
        return \array_values(\array_filter(
            self::getClient()->getAll(),
            static fn (TestRequestContext $context) => $context->getGatewayMethod() === $gatewayMethod,
        ));
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
                $this->createMock(\Swag\PayPal\Checkout\Payment\Service\OrderTransactionService::class),
            ),
            $orderResource,
            $this->createMock(VaultTokenService::class),
            $this->orderTransactionRepo,
            $this->createOrderBuilder($systemConfig),
        );
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
