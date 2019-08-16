<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
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
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Payment\Handler\EcsSpbHandler;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\Payment\Handler\PlusHandler;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandlerTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;
    use KernelTestBehaviour;
    use BasicTestDataBehaviour;

    public const PAYER_ID_PAYMENT_INCOMPLETE = 'testPayerIdIncomplete';
    public const PAYPAL_RESOURCE_THROWS_EXCEPTION = 'createRequestThrowsException';

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

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

    public function testPayWithExceptionDuringPayPalCommunication(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->addExtension(self::PAYPAL_RESOURCE_THROWS_EXCEPTION, new Entity());

        $handler = $this->createPayPalPaymentHandler($settings);

        $paymentTransaction = $this->createPaymentTransactionStruct();
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
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeEcs(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, true);
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeSpb(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set('isPayPalSpbCheckout', true);
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizePlus(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set('isPayPalPlus', true);
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeAuthorization(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE
        );
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_OPEN,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeOrder(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER
        );
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_OPEN,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeWithCancel(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = new Request(['cancel' => true]);
        $context = Generator::createSalesChannelContext();
        $this->expectException(CustomerCanceledAsyncPaymentException::class);
        $this->expectExceptionMessage('The customer canceled the external payment process. Additional information:
Customer canceled the payment on the PayPal page');
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $context
        );
    }

    public function testFinalizePaymentNotCompleted(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID, self::PAYER_ID_PAYMENT_INCOMPLETE);
        $salesChannelContext = Generator::createSalesChannelContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_OPEN,
            $salesChannelContext->getContext()
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeWithException(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->addExtension(self::PAYPAL_RESOURCE_THROWS_EXCEPTION, new Entity());

        $handler = $this->createPayPalPaymentHandler($settings);

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER
        );
        $salesChannelContext = Generator::createSalesChannelContext();
        $this->expectException(AsyncPaymentFinalizeException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );
    }

    private function createPayPalPaymentHandler(?SwagPayPalSettingStruct $settings = null): PayPalPaymentHandler
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $paymentResource = $this->createPaymentResource($settings);
        /** @var EcsSpbHandler $ecsSpbHandler */
        $ecsSpbHandler = $this->getContainer()->get(EcsSpbHandler::class);
        /** @var PlusHandler $plusHandler */
        $plusHandler = $this->getContainer()->get(PlusHandler::class);

        return new PayPalPaymentHandler(
            $paymentResource,
            new OrderTransactionStateHandler($this->orderTransactionRepo, $this->stateMachineRegistry),
            $ecsSpbHandler,
            new PayPalHandler(
                $paymentResource,
                $this->orderTransactionRepo,
                $this->createPaymentBuilder($settings),
                new PayerInfoPatchBuilder(),
                new ShippingAddressPatchBuilder()
            ),
            $plusHandler
        );
    }

    private function createRequest(): Request
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => 'testPayerId',
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'testPaymentId',
        ]);

        return $request;
    }

    private function createCustomer(): CustomerEntity
    {
        $customerId = Uuid::randomHex();
        $addressId = Uuid::randomHex();

        $customer = [
            'id' => $customerId,
            'number' => '1337',
            'salutationId' => $this->getValidSalutationId(),
            'firstName' => 'Max',
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
                    'street' => 'Ebbinghoff 10',
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
}
