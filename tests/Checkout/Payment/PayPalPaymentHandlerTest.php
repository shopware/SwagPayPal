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
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder as OrderNumberPatchBuilderV2;
use Swag\PayPal\OrdersApi\Patch\PurchaseUnitPatchBuilder;
use Swag\PayPal\RestApi\V2\Api\Patch as PatchV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
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
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalPaymentHandlerTest extends TestCase
{
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
    private const TEST_CUSTOMER_STREET = 'Street 1';
    private const TEST_CUSTOMER_FIRST_NAME = 'FirstName';
    private const TEST_CUSTOMER_LAST_NAME = 'LastName';
    private const TEST_AMOUNT = '20028.00';
    private const TEST_SHIPPING = '10.00';

    private EntityRepository $orderTransactionRepo;

    private StateMachineRegistry $stateMachineRegistry;

    private PayPalClientFactoryMock $clientFactory;

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

        $patchData = $this->clientFactory->getClient()->getData();
        static::assertCount(1, $patchData);
        $patch = \current($patchData);
        static::assertInstanceOf(PatchV2::class, $patch);
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

    public function testPayWithEcsThrowsException(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = new PaymentTransactionStruct($transactionId);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: generalClientExceptionMessage');
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

        $patchData = $this->clientFactory->getClient()->getData();
        static::assertCount(1, $patchData);
        $patch = \current($patchData);
        static::assertInstanceOf(PatchV2::class, $patch);
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
        $this->expectExceptionMessage('The customer canceled the external payment process. Customer canceled the payment on the PayPal page');
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
        $this->expectExceptionMessage('The error "UNPROCESSABLE_ENTITY" occurred with the following message: The requested action could not be completed, was semantically incorrect, or failed business validation. The instrument presented  was either declined by the processor or bank, or it can\'t be used for this payment. INSTRUMENT_DECLINED ');

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

    private function createPayPalPaymentHandler(array $settings = []): PayPalPaymentHandler
    {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $this->clientFactory = new PayPalClientFactoryMock(new NullLogger());
        $orderResource = new OrderResource($this->clientFactory);
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
