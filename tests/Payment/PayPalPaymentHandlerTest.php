<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
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

    /**
     * @var DefinitionInstanceRegistryMock
     */
    private $definitionRegistry;

    protected function setUp(): void
    {
        $this->definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $this->orderTransactionRepo = $this->definitionRegistry->getRepository(
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
        $salesChannelContext = Generator::createSalesChannelContext();
        $response = $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $response->getTargetUrl());

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            CreateResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['customFields'][SwagPayPal::PAYPAL_TRANSACTION_CUSTOM_FIELD_NAME]
        );
    }

    public function testPayWithException(): void
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

        return new PayPalPaymentHandler(
            $this->definitionRegistry,
            $this->createPaymentResource($settings),
            $this->createPaymentBuilder($settings),
            new OrderTransactionStateHandler($this->orderTransactionRepo, $this->stateMachineRegistry),
            new OrderTransactionDefinition()
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
}
