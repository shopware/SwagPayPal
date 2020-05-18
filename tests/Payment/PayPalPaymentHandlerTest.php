<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Payment\Handler\AbstractPaymentHandler;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\Payment\Handler\PlusHandler;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\Api\Patch;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandlerTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;
    use KernelTestBehaviour;
    use StateMachineStateTrait;
    use OrderFixture;
    use DatabaseTransactionBehaviour;
    use OrderTransactionTrait;

    public const PAYER_ID_PAYMENT_INCOMPLETE = 'testPayerIdIncomplete';
    public const PAYPAL_RESOURCE_THROWS_EXCEPTION = 'createRequestThrowsException';
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

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->createCustomer()
        );
        $response = $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $response->getTargetUrl());

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            CreateResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['customFields'][SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_TRANSACTION_ID]
        );
    }

    public function testPayWithPlus(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext(
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $this->createCustomer()
        );
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
    }

    public function testPayWithExceptionDuringPayPalCommunication(): void
    {
        $settings = $this->createDefaultSettingStruct();

        $handler = $this->createPayPalPaymentHandler($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', null, self::PAYPAL_RESOURCE_THROWS_EXCEPTION);
        $salesChannelContext = Generator::createSalesChannelContext();

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $handler = $this->createPayPalPaymentHandler($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext();

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Required setting "ClientId" is missing or invalid');
        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testPayWithoutCustomer(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $handler = $this->createPayPalPaymentHandler($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $salesChannelContext = Generator::createSalesChannelContext();

        $testSalesChannelContext = new SalesChannelContext(
            $salesChannelContext->getContext(),
            $salesChannelContext->getToken(),
            $salesChannelContext->getSalesChannel(),
            $salesChannelContext->getCurrency(),
            $salesChannelContext->getCurrentCustomerGroup(),
            $salesChannelContext->getFallbackCustomerGroup(),
            $salesChannelContext->getTaxRules(),
            $salesChannelContext->getPaymentMethod(),
            $salesChannelContext->getShippingMethod(),
            $salesChannelContext->getShippingLocation(),
            null,
            $salesChannelContext->getRuleIds()
        );

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Customer is not logged in.');
        $handler->pay($paymentTransaction, new RequestDataBag(), $testSalesChannelContext);
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

    public function testFinalizeSpb(): void
    {
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_SMART_PAYMENT_BUTTONS_ID, true);
        $this->assertFinalizeRequest($request);
    }

    public function testFinalizePlus(): void
    {
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_PLUS_CHECKOUT_REQUEST_PARAMETER, true);
        $this->assertFinalizeRequest($request);
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
        $this->assertFinalizeRequest($request, OrderTransactionStates::STATE_OPEN);
    }

    public function testFinalizeWithException(): void
    {
        $settings = $this->createDefaultSettingStruct();

        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            self::PAYPAL_RESOURCE_THROWS_EXCEPTION
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

    private function createPayPalPaymentHandler(?SwagPayPalSettingStruct $settings = null): PayPalPaymentHandler
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
            )
        );
    }

    private function createRequest(): Request
    {
        return new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => 'testPayerId',
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'testPaymentId',
        ]);
    }

    private function createCustomer(): CustomerEntity
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => self::TEST_CUSTOMER_FIRST_NAME,
            'lastName' => 'Mustermann',
            'customerNumber' => '1337',
            'email' => Uuid::randomHex() . '@example.com',
            'password' => 'shopware',
            'defaultPaymentMethodId' => $this->getValidPaymentMethodId(),
            'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
            'salesChannelId' => Defaults::SALES_CHANNEL,
            'defaultBillingAddressId' => $addressId,
            'defaultShippingAddressId' => $addressId,
            'addresses' => [
                [
                    'id' => $addressId,
                    'customerId' => $customerId,
                    'countryId' => $this->getValidCountryId(),
                    'salutationId' => $this->getValidSalutationId(),
                    'firstName' => 'Max',
                    'lastName' => 'Mustermann',
                    'street' => self::TEST_CUSTOMER_STREET,
                    'zipcode' => '48624',
                    'city' => 'Schöppingen',
                ],
            ],
        ];

        /** @var EntityRepositoryInterface $customerRepo */
        $customerRepo = $this->getContainer()->get('customer.repository');
        $context = Context::createDefaultContext();
        $customerRepo->upsert([$customer], $context);

        $criteria = (new Criteria([$customerId]))
            ->addAssociation('defaultBillingAddress.country')
            ->addAssociation('defaultShippingAddress.country');

        return $customerRepo->search($criteria, $context)->first();
    }

    private function assertFinalizeRequest(
        Request $request,
        string $state = OrderTransactionStates::STATE_PAID
    ): void {
        $handler = $this->createPayPalPaymentHandler();

        $salesChannelContext = Generator::createSalesChannelContext();
        $container = $this->getContainer();

        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $container);
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );

        $expectedStateId = $this->getOrderTransactionStateIdByTechnicalName(
            $state,
            $container,
            $salesChannelContext->getContext()
        );

        $transaction = $this->getTransaction($transactionId, $container, $salesChannelContext->getContext());
        static::assertNotNull($transaction);
        static::assertNotNull($expectedStateId);
        static::assertSame($expectedStateId, $transaction->getStateId());
    }
}
