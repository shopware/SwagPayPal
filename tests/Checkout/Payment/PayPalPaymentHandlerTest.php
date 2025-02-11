<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Order\OrderConverter;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
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
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Patch as PatchV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
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
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
    private const TEST_AMOUNT = '860.00';
    private const TEST_SHIPPING = '4.99';

    private EntityRepository $orderTransactionRepo;

    private StateMachineRegistry $stateMachineRegistry;

    private PayPalClientFactoryMock $clientFactory;

    protected function setUp(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], $this->createMock(ContainerInterface::class));
        $this->orderTransactionRepo = $definitionRegistry->getRepository(
            (new OrderTransactionDefinition())->getEntityName()
        );
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
    }

    public function testPay(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $response = $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);

        static::assertSame(CreateOrderCapture::APPROVE_URL, $response->getTargetUrl());

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            CreateOrderCapture::ID,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID]
        );

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithEcs(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId);
        $paypalOrderId = GetOrderCapture::ID;
        $dataBag = new RequestDataBag([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId,
        ]);

        $response = $handler->pay($paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame(
            \sprintf(
                '%s&token=%s&%s=1',
                ConstantsForTesting::PAYMENT_TRANSACTION_DOMAIN,
                $paypalOrderId,
                PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID
            ),
            $response->getTargetUrl()
        );

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            $paypalOrderId,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID]
        );

        static::assertSame(
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID]
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

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_UNCONFIRMED, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithEcsThrowsException(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $dataBag = new RequestDataBag([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_PATCH_THROWS_EXCEPTION,
        ]);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The error "TEST" occurred with the following message: generalClientExceptionMessage');
        $handler->pay($paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testPayWithSpb(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $paypalOrderId = GetOrderCapture::ID;
        $dataBag = new RequestDataBag([
            AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId,
        ]);

        $response = $handler->pay($paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame(
            \sprintf(
                '%s&token=%s&%s=1',
                ConstantsForTesting::PAYMENT_TRANSACTION_DOMAIN,
                $paypalOrderId,
                PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID
            ),
            $response->getTargetUrl()
        );

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            $paypalOrderId,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID]
        );

        static::assertSame(
            PartnerAttributionId::SMART_PAYMENT_BUTTONS,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID]
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

    public function testPayWithExceptionDuringPayPalCommunication(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = $this->createPaymentTransactionStruct(
            'some-order-id',
            $transactionId,
            ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION
        );

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
A PayPal test error occurred.');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $handler = $this->createPayPalPaymentHandler();
        $salesChannelContext = Generator::createSalesChannelContext();
        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $this->getContainer());
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Required setting "SwagPayPal.settings.clientId" is missing or invalid');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithoutCustomer(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection(),
            null,
            false
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Customer is not logged in.');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithoutApprovalURL(): void
    {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler(\array_merge($settings, [Settings::SEND_ORDER_NUMBER => true]));

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct(
            'some-order-id',
            $transactionId,
            ConstantsForTesting::PAYPAL_RESPONSE_HAS_NO_APPROVAL_URL
        );
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
No approve link provided by PayPal');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testFinalizeWithCancel(): void
    {
        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The customer canceled the external payment process. Customer canceled the payment on the PayPal page');
        $this->createPayPalPaymentHandler()->finalize(
            $this->createPaymentTransactionStruct(
                ConstantsForTesting::VALID_ORDER_ID,
                'testTransactionId',
                null,
                $this->getContainer(),
                Context::createDefaultContext()
            ),
            new Request([PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_CANCEL => true]),
            Generator::createSalesChannelContext()
        );
    }

    public function testFinalizePayPalOrderCapture(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => GetOrderCapture::ID,
        ]);
        $this->assertFinalizeRequest($request);
        $this->assertCustomFields(CaptureOrderCapture::CAPTURE_ID);
    }

    public function testFinalizePayPalOrderAuthorize(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => GetOrderAuthorization::ID,
        ]);
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_AUTHORIZED);
        $this->assertCustomFields(GetAuthorization::ID);
    }

    public function testFinalizePayPalOrderCaptureWithException(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => self::PAYPAL_ORDER_ID_INSTRUMENT_DECLINED,
        ]);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
The error "UNPROCESSABLE_ENTITY" occurred with the following message: The requested action could not be completed, was semantically incorrect, or failed business validation. The instrument presented  was either declined by the processor or bank, or it can\'t be used for this payment. INSTRUMENT_DECLINED ');

        $this->assertFinalizeRequest($request);
    }

    public function testFinalizePayPalOrderPatchOrderNumber(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => GetOrderCapture::ID,
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
        ]);
        $this->assertFinalizeRequest($request);
        $this->assertCustomFields(CaptureOrderCapture::CAPTURE_ID);
    }

    public function testFinalizePayPalOrderPatchOrderNumberDuplicate(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => self::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER,
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
        ]);
        CaptureOrderCapture::setDuplicateOrderNumber(true);
        $this->assertFinalizeRequest($request);
        $this->assertCustomFields(CaptureOrderCapture::CAPTURE_ID);
    }

    public function testFinalizeWontCancelFinalizedTransactions(): void
    {
        $orderTransactionEntity = $this->createOrderTransaction(Uuid::randomHex());
        $context = Context::createDefaultContext();
        $stateId = $this->getOrderTransactionStateIdByTechnicalName(OrderTransactionStates::STATE_PAID, $this->getContainer(), $context);
        static::assertNotNull($stateId);
        $orderTransactionEntity->setStateId(
            $stateId
        );
        $salesChannelContextMock = $this->createMock(SalesChannelContext::class);
        $salesChannelContextMock->expects(static::never())->method('getSalesChannel')->withAnyParameters();
        $salesChannelContextMock->expects(static::once())->method('getContext')->withAnyParameters()->willReturn($context);

        /** @var EntityRepository $stateMachineStateRepository */
        $stateMachineStateRepository = $this->getContainer()->get(\sprintf('%s.repository', StateMachineStateDefinition::ENTITY_NAME));

        $this->createPayPalPaymentHandler(
            [],
            $stateMachineStateRepository
        )->finalize(
            new AsyncPaymentTransactionStruct(
                $orderTransactionEntity,
                $this->createOrderEntity(Uuid::randomHex()),
                'https://example.com'
            ),
            new Request([PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_CANCEL => true]),
            $salesChannelContextMock
        );
    }

    private function createPayPalPaymentHandler(
        array $settings = [],
        ?EntityRepository $orderTransactionRepository = null,
    ): PayPalPaymentHandler {
        $systemConfig = $this->createSystemConfigServiceMock($settings);
        $this->clientFactory = new PayPalClientFactoryMock(new NullLogger());
        $orderResource = new OrderResource($this->clientFactory);
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $logger = new NullLogger();
        /** @var EntityRepository&MockObject $orderTransactionRepositoryMock */
        $orderTransactionRepositoryMock = $this->createMock(EntityRepository::class);
        $paymentBuilder = $this->createPaymentBuilder($systemConfig);

        return new PayPalPaymentHandler(
            $orderTransactionStateHandler,
            new PayPalHandler(
                $this->createOrderBuilder($systemConfig),
                $orderResource,
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
                $this->createMock(VaultTokenService::class),
                $logger
            ),
            $orderTransactionRepository ?? $orderTransactionRepositoryMock,
            $logger,
            new SettingsValidationService($systemConfig, new NullLogger()),
            $this->createMock(VaultTokenService::class),
            $this->createMock(OrderConverter::class),
        );
    }

    private function assertFinalizeRequest(
        Request $request,
        string $state = OrderTransactionStates::STATE_PAID,
    ): string {
        $settings = $this->getDefaultConfigData();
        $handler = $this->createPayPalPaymentHandler($settings);

        $salesChannelContext = Generator::createSalesChannelContext();
        $container = $this->getContainer();

        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $container);
        $handler->finalize(
            $this->createPaymentTransactionStruct(
                ConstantsForTesting::VALID_ORDER_ID,
                $transactionId,
                null,
                $this->getContainer(),
                $salesChannelContext->getContext()
            ),
            $request,
            $salesChannelContext
        );

        $this->assertOrderTransactionState($state, $transactionId, $salesChannelContext->getContext());

        return $transactionId;
    }

    private function assertCustomFields(?string $resourceId): void
    {
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        static::assertSame($resourceId, $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID]);
    }
}
