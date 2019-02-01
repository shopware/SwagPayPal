<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Core\Checkout\Payment\Cart\PaymentHandler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\StateMachine\StateMachineRegistry;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagPayPal\Core\Checkout\Payment\Cart\PaymentHandler\PayPalPayment;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentTest extends TestCase
{
    use PaymentTransactionTrait,
        ServicesTrait,
        KernelTestBehaviour;

    public const PAYER_ID_PAYMENT_INCOMPLETE = 'testPayerIdIncomplete';

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    /**
     * @var StateMachineRegistry
     */
    private $stateMachineRegistry;

    protected function setUp(): void
    {
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        $this->stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
    }

    public function testPay(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $paymentTransaction = $this->createPaymentTransactionStruct();
        $context = Context::createDefaultContext();
        $response = $handler->pay($paymentTransaction, $context);

        self::assertNotNull($response);
        if ($response === null) {
            return;
        }

        self::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $response->getTargetUrl());

        $updatedData = $this->orderTransactionRepo->getData();
        self::assertSame(
            CreateResponseFixture::CREATE_PAYMENT_ID,
            $updatedData['details'][PayPalPayment::TRANSACTION_DETAILS_JSON_KEY]['transactionId']
        );
    }

    public function testFinalizeSale(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_PAID,
            $context
        )->getId();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeAuthorization(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(
            PayPalPayment::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE
        );
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_OPEN,
            $context
        )->getId();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeOrder(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(
            PayPalPayment::PAYPAL_REQUEST_PARAMETER_PAYER_ID,
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER
        );
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_OPEN,
            $context
        )->getId();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizeWithCancel(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = new Request(['cancel' => true]);
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_CANCELLED,
            $context
        )->getId();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame($expectedStateId, $updatedData['stateId']);
    }

    public function testFinalizePaymentNotCompleted(): void
    {
        $handler = $this->createPayPalPaymentHandler();

        $transactionId = 'testTransactionId';
        $request = $this->createRequest();
        $request->query->set(PayPalPayment::PAYPAL_REQUEST_PARAMETER_PAYER_ID, self::PAYER_ID_PAYMENT_INCOMPLETE);
        $context = Context::createDefaultContext();
        $handler->finalize($transactionId, $request, $context);
        $updatedData = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_OPEN,
            $context
        )->getId();

        self::assertSame($transactionId, $updatedData['id']);
        self::assertSame($expectedStateId, $updatedData['stateId']);
    }

    private function createPayPalPaymentHandler(): PayPalPayment
    {
        $settingsProvider = new SettingsProviderMock();

        return new PayPalPayment(
            $this->orderTransactionRepo,
            $this->createPaymentResource($settingsProvider),
            $this->createPaymentBuilder($settingsProvider),
            $this->stateMachineRegistry
        );
    }

    private function createRequest(): Request
    {
        $request = new Request([
            PayPalPayment::PAYPAL_REQUEST_PARAMETER_PAYER_ID => 'testPayerId',
            PayPalPayment::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'testPaymentId',
        ]);

        return $request;
    }
}
