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
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateDefinition;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Handler\PlusPuiHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Patch\AmountPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\CustomIdPatchBuilder;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder as OrderNumberPatchBuilderV2;
use Swag\PayPal\OrdersApi\Patch\ShippingAddressPatchBuilder as ShippingAddressPatchBuilderV2;
use Swag\PayPal\OrdersApi\Patch\ShippingNamePatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\CustomTransactionPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\RestApi\V2\Api\Patch as PatchV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\ExecutePaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\PaymentsApi\Builder\OrderPaymentBuilderTest;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandlerTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;
    use StateMachineStateTrait;
    use OrderFixture;
    use DatabaseTransactionBehaviour;
    use OrderTransactionTrait;
    use SalesChannelContextTrait;

    public const PAYER_ID_PAYMENT_INCOMPLETE = 'testPayerIdIncomplete';
    public const PAYER_ID_DUPLICATE_TRANSACTION = 'testPayerIdDuplicateTransaction';
    public const PAYPAL_PATCH_THROWS_EXCEPTION = 'invalidId';
    public const PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER = 'paypalOrderIdDuplicateOrderNumber';
    private const TEST_CUSTOMER_STREET = 'Ebbinghoff 10';
    private const TEST_CUSTOMER_FIRST_NAME = 'Max';
    private const TEST_CUSTOMER_LAST_NAME = 'Mustermann';
    private const TEST_AMOUNT = '860.00';
    private const TEST_SHIPPING = '4.99';

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    /**
     * @var PayPalClientFactoryMock
     */
    private $clientFactory;

    protected function setUp(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $this->orderTransactionRepo = $definitionRegistry->getRepository(
            (new OrderTransactionDefinition())->getEntityName()
        );
        /** @var StateMachineRegistry $stateMachineRegistry */
        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    public function testPay(): void
    {
        $handler = $this->createPayPalPaymentHandler();

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

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_IN_PROGRESS, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithPlus(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $dataBag = new RequestDataBag();
        $dataBag->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, true);
        $dataBag->set(PlusPuiHandler::PAYPAL_PAYMENT_ID_INPUT_NAME, CreateResponseFixture::CREATE_PAYMENT_ID);
        $response = $handler->pay($paymentTransaction, $dataBag, $salesChannelContext);

        static::assertSame('plusPatched', $response->getTargetUrl());

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            CreateResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID]
        );

        $patchData = $this->clientFactory->getClient()->getData();
        static::assertCount(3, $patchData);
        foreach ($patchData as $patch) {
            static::assertInstanceOf(Patch::class, $patch);
            if ($patch->getPath() === '/transactions/0/item_list/shipping_address') {
                $patchValue = $patch->getValue();
                static::assertIsArray($patchValue);
                static::assertSame(self::TEST_CUSTOMER_STREET, $patchValue['line1']);
            }

            if ($patch->getPath() === '/payer/payer_info') {
                $patchValue = $patch->getValue();
                static::assertIsArray($patchValue);
                static::assertSame(self::TEST_CUSTOMER_FIRST_NAME, $patchValue['first_name']);
                static::assertSame(self::TEST_CUSTOMER_STREET, $patchValue['billing_address']['line1']);
            }

            if ($patch->getPath() === '/transactions/0/custom') {
                static::assertSame($transactionId, $patch->getValue());
            }
        }

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_IN_PROGRESS, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithPlusThrowsException(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $dataBag = new RequestDataBag();
        $dataBag->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_ID, true);
        $dataBag->set(PlusPuiHandler::PAYPAL_PAYMENT_ID_INPUT_NAME, self::PAYPAL_PATCH_THROWS_EXCEPTION);
        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
The asynchronous payment process was interrupted due to the following error:
An error occurred during the communication with PayPal
The error "TEST" occurred with the following message: generalClientExceptionMessage');
        $handler->pay($paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testPayWithEcs(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $paypalOrderId = 'paypalOrderId';
        $dataBag = new RequestDataBag([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            EcsSpbHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId,
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
        static::assertCount(3, $patchData);
        foreach ($patchData as $patch) {
            static::assertInstanceOf(PatchV2::class, $patch);
            if ($patch->getPath() === "/purchase_units/@reference_id=='default'/shipping/address") {
                $patchValue = $patch->getValue();
                static::assertIsArray($patchValue);
                static::assertSame(self::TEST_CUSTOMER_STREET, $patchValue['address_line_1']);
            }

            if ($patch->getPath() === "/purchase_units/@reference_id=='default'/shipping/name") {
                $patchValue = $patch->getValue();
                static::assertIsArray($patchValue);
                static::assertSame(\sprintf('%s %s', self::TEST_CUSTOMER_FIRST_NAME, self::TEST_CUSTOMER_LAST_NAME), $patchValue['full_name']);
            }

            if ($patch->getPath() === "/purchase_units/@reference_id=='default'/amount") {
                $patchValue = $patch->getValue();
                static::assertIsArray($patchValue);
                static::assertSame(self::TEST_AMOUNT, $patchValue['value']);
                static::assertSame(self::TEST_SHIPPING, $patchValue['breakdown']['shipping']['value']);
            }
        }

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_IN_PROGRESS, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithEcsThrowsException(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $dataBag = new RequestDataBag([
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
            EcsSpbHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_PATCH_THROWS_EXCEPTION,
        ]);

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
The asynchronous payment process was interrupted due to the following error:
An error occurred during the communication with PayPal
The error "TEST" occurred with the following message: generalClientExceptionMessage');
        $handler->pay($paymentTransaction, $dataBag, $salesChannelContext);
    }

    public function testPayWithSpb(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $paypalOrderId = 'paypalOrderId';
        $dataBag = new RequestDataBag([
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID => true,
            EcsSpbHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId,
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
    }

    public function testPayWithExceptionDuringPayPalCommunication(): void
    {
        $settings = $this->createDefaultSettingStruct();

        $handler = $this->createPayPalPaymentHandler($settings);

        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $paymentTransaction = $this->createPaymentTransactionStruct(
            'some-order-id',
            $transactionId,
            ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION
        );

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $handler = $this->createPayPalPaymentHandler($settings);
        $salesChannelContext = Generator::createSalesChannelContext();
        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $this->getContainer());
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Required setting "ClientId" is missing or invalid');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithoutCustomer(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection(),
            null,
            false
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);
        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Customer is not logged in.');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithoutApprovalURL(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setSendOrderNumber(true);
        $handler = $this->createPayPalPaymentHandler($settings);

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
        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
No approve link provided by PayPal');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testFinalizeSale(): void
    {
        $this->assertFinalizeRequest($this->createPaymentV1Request());
    }

    public function testFinalizeEcs(): void
    {
        $request = $this->createPaymentV1Request();
        $request->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizeEcsWithDuplicateTransaction(): void
    {
        ExecutePaymentSaleResponseFixture::setDuplicateTransaction(true);
        $request = $this->createPaymentV1Request(self::PAYER_ID_DUPLICATE_TRANSACTION);
        $request->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizeSpb(): void
    {
        $request = $this->createPaymentV1Request();
        $request->query->set(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizeSpbWithDuplicateTransaction(): void
    {
        ExecutePaymentSaleResponseFixture::setDuplicateTransaction(true);
        $request = $this->createPaymentV1Request(self::PAYER_ID_DUPLICATE_TRANSACTION);
        $request->query->set(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizePlus(): void
    {
        $request = $this->createPaymentV1Request();
        $request->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizePlusWithDuplicateTransaction(): void
    {
        ExecutePaymentSaleResponseFixture::setDuplicateTransaction(true);
        $request = $this->createPaymentV1Request(self::PAYER_ID_DUPLICATE_TRANSACTION);
        $request->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizeAuthorization(): void
    {
        $request = $this->createPaymentV1Request();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE
        );
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_OPEN);
    }

    public function testFinalizeOrder(): void
    {
        $request = $this->createPaymentV1Request();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER
        );
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_OPEN);
    }

    public function testFinalizeWithCancel(): void
    {
        $this->expectException(CustomerCanceledAsyncPaymentException::class);
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

    public function testFinalizePaymentNotCompleted(): void
    {
        $request = $this->createPaymentV1Request();
        $request->query->set(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID, self::PAYER_ID_PAYMENT_INCOMPLETE);
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_FAILED);
    }

    public function testFinalizeWithException(): void
    {
        $settings = $this->createDefaultSettingStruct();

        $request = $this->createPaymentV1Request();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION
        );

        $this->expectException(AsyncPaymentFinalizeException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $this->createPayPalPaymentHandler($settings)->finalize(
            $this->createPaymentTransactionStruct(
                ConstantsForTesting::VALID_ORDER_ID,
                'testTransactionId',
                null,
                $this->getContainer(),
                Context::createDefaultContext()
            ),
            $request,
            Generator::createSalesChannelContext()
        );
    }

    public function testFinalizePayPalOrder(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => 'paypalOrderId',
        ]);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizePayPalOrderPatchOrderNumber(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => 'paypalOrderId',
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
        ]);
        $orderTransactionId = $this->assertFinalizeRequest($request);

        $patchData = $this->clientFactory->getClient()->getData();
        static::assertCount(2, $patchData);
        foreach ($patchData as $patch) {
            static::assertInstanceOf(PatchV2::class, $patch);
            if ($patch->getPath() === "/purchase_units/@reference_id=='default'/invoice_id") {
                $patchValue = $patch->getValue();
                static::assertSame(OrderPaymentBuilderTest::TEST_ORDER_NUMBER, $patchValue);
                static::assertSame(PatchV2::OPERATION_ADD, $patch->getOp());
            }

            if ($patch->getPath() === "/purchase_units/@reference_id=='default'/custom_id") {
                $patchValue = $patch->getValue();
                static::assertSame($orderTransactionId, $patchValue);
                static::assertSame(PatchV2::OPERATION_ADD, $patch->getOp());
            }
        }
    }

    public function testFinalizePayPalOrderPatchOrderNumberDuplicate(): void
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => self::PAYPAL_ORDER_ID_DUPLICATE_ORDER_NUMBER,
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID => true,
        ]);
        CaptureOrderCapture::setDuplicateOrderNumber(true);
        $this->assertFinalizeRequest($request);

        $patchData = $this->clientFactory->getClient()->getData();
        static::assertCount(1, $patchData);
        foreach ($patchData as $patch) {
            static::assertInstanceOf(PatchV2::class, $patch);
            if ($patch->getPath() === "/purchase_units/@reference_id=='default'/invoice_id") {
                static::assertSame(PatchV2::OPERATION_REMOVE, $patch->getOp());
            }
        }
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

        /** @var EntityRepositoryInterface $stateMachineStateRepository */
        $stateMachineStateRepository = $this->getContainer()->get(\sprintf('%s.repository', StateMachineStateDefinition::ENTITY_NAME));

        $this->createPayPalPaymentHandler(
            null,
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
        ?SwagPayPalSettingStruct $settings = null,
        ?EntityRepositoryInterface $orderTransactionRepository = null
    ): PayPalPaymentHandler {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $this->clientFactory = $this->createPayPalClientFactory($settings);
        $orderResource = new OrderResource($this->clientFactory);
        /** @var EntityRepositoryInterface $currencyRepository */
        $currencyRepository = $this->getContainer()->get('currency.repository');
        $orderTransactionStateHandler = new OrderTransactionStateHandler($this->stateMachineRegistry);
        $settingsService = new SettingsServiceMock($settings);
        $priceFormatter = new PriceFormatter();
        $logger = new NullLogger();
        /** @var EntityRepositoryInterface $orderTransactionRepositoryMock */
        $orderTransactionRepositoryMock = $this->createMock(EntityRepositoryInterface::class);

        return new PayPalPaymentHandler(
            $orderTransactionStateHandler,
            new EcsSpbHandler(
                $this->orderTransactionRepo,
                $settingsService,
                $currencyRepository,
                new ShippingAddressPatchBuilderV2(),
                new ShippingNamePatchBuilder(),
                new AmountPatchBuilder(new AmountProvider($priceFormatter)),
                $orderResource,
                new ItemListProvider($priceFormatter, new EventDispatcherMock(), new LoggerMock())
            ),
            new PayPalHandler(
                $this->orderTransactionRepo,
                $this->createOrderBuilder($settings),
                $orderResource,
                $orderTransactionStateHandler,
                $settingsService,
                new OrderNumberPatchBuilderV2(),
                new CustomIdPatchBuilder(),
                $logger
            ),
            new PlusPuiHandler(
                new PaymentResource($this->clientFactory),
                $this->orderTransactionRepo,
                $this->createPaymentBuilder($settings),
                new PayerInfoPatchBuilder(),
                new OrderNumberPatchBuilder(),
                new CustomTransactionPatchBuilder(),
                new ShippingAddressPatchBuilder(),
                $settingsService,
                $orderTransactionStateHandler,
                $logger
            ),
            $orderTransactionRepository ?? $orderTransactionRepositoryMock
        );
    }

    private function createPaymentV1Request(?string $payerId = null): Request
    {
        return new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => $payerId ?? 'testPayerId',
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'testPaymentId',
        ]);
    }

    private function assertFinalizeRequest(
        Request $request,
        string $state = OrderTransactionStates::STATE_PAID
    ): string {
        $handler = $this->createPayPalPaymentHandler();

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
}
