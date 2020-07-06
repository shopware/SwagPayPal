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
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Checkout\Payment\Handler\AbstractPaymentHandler;
use Swag\PayPal\Checkout\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\Handler\PlusHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\PaymentsApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\PayPal\ApiV1\Api\Patch;
use Swag\PayPal\PayPal\ApiV1\Resource\PaymentResource;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\ExecutePaymentSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Payment\Builder\OrderPaymentBuilderTest;
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
    private const TEST_CUSTOMER_STREET = 'Ebbinghoff 10';
    private const TEST_CUSTOMER_FIRST_NAME = 'Max';

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

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $response->getTargetUrl());

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            CreateResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID]
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
        $dataBag->set(AbstractPaymentHandler::PAYPAL_PAYMENT_ID_INPUT_NAME, CreateResponseFixture::CREATE_PAYMENT_ID);
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
        static::assertCount(2, $patchData);
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
        }

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_IN_PROGRESS, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithExceptionDuringPayPalCommunication(): void
    {
        $settings = $this->createDefaultSettingStruct();

        $handler = $this->createPayPalPaymentHandler($settings);

        $salesChannelContext = Generator::createSalesChannelContext();
        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $this->getContainer());
        $paymentTransaction = $this->createPaymentTransactionStruct(
            'some-order-id',
            $transactionId,
            ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION
        );

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_OPEN, $transactionId, $salesChannelContext->getContext());
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

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_OPEN, $transactionId, $salesChannelContext->getContext());
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

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_OPEN, $transactionId, $salesChannelContext->getContext());
    }

    public function testFinalizeSale(): void
    {
        $this->assertFinalizeRequest($this->createRequest());
    }

    public function testFinalizeEcs(): void
    {
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizeEcsWithDuplicateTransaction(): void
    {
        ExecutePaymentSaleResponseFixture::setDuplicateTransaction(true);
        $request = $this->createRequest(self::PAYER_ID_DUPLICATE_TRANSACTION);
        $request->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_PAID, true);
    }

    public function testFinalizeSpb(): void
    {
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizeSpbWithDuplicateTransaction(): void
    {
        ExecutePaymentSaleResponseFixture::setDuplicateTransaction(true);
        $request = $this->createRequest(self::PAYER_ID_DUPLICATE_TRANSACTION);
        $request->query->set(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID, true);
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_PAID, true);
    }

    public function testFinalizePlus(): void
    {
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizePlusWithDuplicateTransaction(): void
    {
        ExecutePaymentSaleResponseFixture::setDuplicateTransaction(true);
        $request = $this->createRequest(self::PAYER_ID_DUPLICATE_TRANSACTION);
        $request->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER, true);
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_PAID, true);
    }

    public function testFinalizeAuthorization(): void
    {
        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE
        );
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_OPEN);
    }

    public function testFinalizeOrder(): void
    {
        $request = $this->createRequest();
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
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, 'testTransactionId'),
            new Request(['cancel' => true]),
            Generator::createSalesChannelContext()
        );
    }

    public function testFinalizePaymentNotCompleted(): void
    {
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID, self::PAYER_ID_PAYMENT_INCOMPLETE);
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_FAILED);
    }

    public function testFinalizeWithException(): void
    {
        $settings = $this->createDefaultSettingStruct();

        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYPAL_RESOURCE_THROWS_EXCEPTION
        );

        $this->expectException(AsyncPaymentFinalizeException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $this->createPayPalPaymentHandler($settings)->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, 'testTransactionId'),
            $request,
            Generator::createSalesChannelContext()
        );
    }

    private function createPayPalPaymentHandler(?SwagPayPalSettingStruct $settings = null, ?OrderNumberPatchBuilder $orderNumberPatchBuilder = null): PayPalPaymentHandler
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $this->clientFactory = $this->createPayPalClientFactory($settings);
        $paymentResource = new PaymentResource($this->clientFactory);
        /** @var EcsSpbHandler $ecsSpbHandler */
        $ecsSpbHandler = $this->getContainer()->get(EcsSpbHandler::class);
        $payerInfoPatchBuilder = new PayerInfoPatchBuilder();
        $shippingAddressPatchBuilder = new ShippingAddressPatchBuilder();

        return new PayPalPaymentHandler(
            $paymentResource,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            $this->orderTransactionRepo,
            $ecsSpbHandler,
            new PayPalHandler(
                $paymentResource,
                $this->orderTransactionRepo,
                $this->createPaymentBuilder($settings),
                $payerInfoPatchBuilder,
                $shippingAddressPatchBuilder
            ),
            new PlusHandler(
                $paymentResource,
                $this->orderTransactionRepo,
                $payerInfoPatchBuilder,
                $shippingAddressPatchBuilder
            ),
            $orderNumberPatchBuilder ?? new OrderNumberPatchBuilder(),
            new SettingsServiceMock($settings),
            new NullLogger()
        );
    }

    private function createRequest(?string $payerId = null): Request
    {
        return new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => $payerId ?? 'testPayerId',
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'testPaymentId',
        ]);
    }

    private function assertFinalizeRequest(
        Request $request,
        string $state = OrderTransactionStates::STATE_PAID,
        bool $isDuplicateTransaction = false
    ): void {
        $orderNumberPatchActions = [
            PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID,
            PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID,
            PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER,
        ];

        $addOrderNumberPatchMock = false;
        foreach ($orderNumberPatchActions as $action) {
            if ($addOrderNumberPatchMock || !$request->query->getBoolean($action)) {
                continue;
            }

            $addOrderNumberPatchMock = true;
        }

        if (!$addOrderNumberPatchMock) {
            $handler = $this->createPayPalPaymentHandler();
        } else {
            $orderNumberPatchMock = $this->getMockBuilder(OrderNumberPatchBuilder::class)->getMock();
            $orderNumberPatchMock->expects(static::exactly($isDuplicateTransaction ? 2 : 1))
                ->method('createOrderNumberPatch')
                ->withConsecutive([OrderPaymentBuilderTest::TEST_ORDER_NUMBER], [null])
                ->willReturn(new Patch());

            $handler = $this->createPayPalPaymentHandler(null, $orderNumberPatchMock);
        }

        $salesChannelContext = Generator::createSalesChannelContext();
        $container = $this->getContainer();

        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $container);
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );

        $this->assertOrderTransactionState($state, $transactionId, $salesChannelContext->getContext());
    }
}
