<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use SwagPayPal\Payment\PayPalPaymentHandler;
use SwagPayPal\SwagPayPal;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\DIContainerMock;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use SwagPayPal\Test\Mock\Repositories\DefinitionRegistryMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentHandlerTest extends TestCase
{
    use PaymentTransactionTrait,
        ServicesTrait,
        KernelTestBehaviour;

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
     * @var DefinitionRegistryMock
     */
    private $definitionRegistry;

    protected function setUp(): void
    {
        $this->definitionRegistry = new DefinitionRegistryMock([], new DIContainerMock());
        $this->orderTransactionRepo = $this->definitionRegistry->getRepository(OrderTransactionDefinition::getEntityName());
        /** @var StateMachineRegistry $stateMachineRegistry */
        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    public function testPay(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $response = $handler->pay($paymentTransaction, $context);

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $response->getTargetUrl());

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();
        static::assertSame(
            CreateResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['attributes'][SwagPayPal::PAYPAL_TRANSACTION_ATTRIBUTE_NAME]
        );
    }

    public function testPayWithException(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $context->addExtension(self::PAYPAL_RESOURCE_THROWS_EXCEPTION, new Entity());

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $handler->pay($paymentTransaction, $context);
    }

    public function testFinalizeSale(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $context = Context::createDefaultContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $context
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $context
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
        $context = Context::createDefaultContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $context
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_OPEN,
            $context
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
        $context = Context::createDefaultContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $context
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_OPEN,
            $context
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeWithCancel(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = new Request(['cancel' => true]);
        $context = Context::createDefaultContext();
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
        $context = Context::createDefaultContext();
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $context
        );
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedData = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_OPEN,
            $context
        )->getId();

        static::assertSame($transactionId, $updatedData['id']);
        static::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeWithException(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER
        );
        $context = Context::createDefaultContext();
        $context->addExtension(self::PAYPAL_RESOURCE_THROWS_EXCEPTION, new Entity());
        $this->expectException(AsyncPaymentFinalizeException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
An error occurred during the communication with PayPal');
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $context
        );
    }

    private function createPayPalPaymentHandler(): PayPalPaymentHandler
    {
        $settingsProvider = new SettingsServiceMock($this->definitionRegistry);

        return new PayPalPaymentHandler(
            $this->definitionRegistry,
            $this->createPaymentResource($settingsProvider),
            $this->createPaymentBuilder($settingsProvider),
            $this->stateMachineRegistry
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
