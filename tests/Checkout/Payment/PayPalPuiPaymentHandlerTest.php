<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Checkout\Payment\Handler\PayPalHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\PaymentsApi\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\PaymentsApi\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPuiPaymentHandlerTest extends TestCase
{
    use ServicesTrait;
    use PaymentTransactionTrait;
    use OrderFixture;
    use SalesChannelContextTrait;
    use StateMachineStateTrait;
    use OrderTransactionTrait;
    use DatabaseTransactionBehaviour;

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
        $handler = $this->createPayPalPuiPaymentHandler();

        $transactionId = $this->getTransactionId(Context::createDefaultContext(), $this->getContainer());
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection()
        );
        $paymentTransaction = $this->createPaymentTransactionStruct('some-order-id', $transactionId);

        $handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
        $this->assertOrderTransactionState(OrderTransactionStates::STATE_IN_PROGRESS, $transactionId, $salesChannelContext->getContext());
    }

    public function testPayWithoutCustomer(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $handler = $this->createPayPalPuiPaymentHandler($settings);

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

    public function testFinalize(): void
    {
        $handler = $this->createPayPalPuiPaymentHandler();

        $request = $this->createRequest();
        $salesChannelContext = Generator::createSalesChannelContext();
        $container = $this->getContainer();
        $transactionId = $this->getTransactionId($salesChannelContext->getContext(), $container);
        $handler->finalize(
            $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID, $transactionId),
            $request,
            $salesChannelContext
        );

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PAID, $transactionId, $salesChannelContext->getContext());
    }

    private function createRequest(): Request
    {
        return new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => ConstantsForTesting::PAYER_ID_PAYMENT_PUI,
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'PAYID-LUWEJRI80B04311G7544303K',
        ]);
    }

    private function createPayPalPuiPaymentHandler(?SwagPayPalSettingStruct $settings = null): PayPalPuiPaymentHandler
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $paymentResource = $this->createPaymentResource($settings);

        return new PayPalPuiPaymentHandler(
            new PayPalHandler(
                $paymentResource,
                $this->orderTransactionRepo,
                $this->createPaymentBuilder($settings),
                new PayerInfoPatchBuilder(),
                new ShippingAddressPatchBuilder()
            ),
            $paymentResource,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            $this->orderTransactionRepo
        );
    }
}
