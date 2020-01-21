<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPuiPaymentHandlerTest extends TestCase
{
    use KernelTestBehaviour;
    use ServicesTrait;
    use PaymentTransactionTrait;
    use OrderFixture;
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

        $expectedStateId = $this->getOrderTransactionStateIdByTechnicalName(
            OrderTransactionStates::STATE_PAID,
            $container,
            $salesChannelContext->getContext()
        );

        $transaction = $this->getTransaction($transactionId, $container, $salesChannelContext->getContext());
        static::assertNotNull($transaction);
        static::assertNotNull($expectedStateId);
        static::assertSame($expectedStateId, $transaction->getStateId());
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
