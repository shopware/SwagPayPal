<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\Payment\Handler\PayPalHandler;
use Swag\PayPal\Payment\Patch\PayerInfoPatchBuilder;
use Swag\PayPal\Payment\Patch\ShippingAddressPatchBuilder;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\Payment\PayPalPuiPaymentHandler;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPuiPaymentHandlerTest extends TestCase
{
    use KernelTestBehaviour;
    use ServicesTrait;
    use PaymentTransactionTrait;

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

        $transactionId = mb_strtolower('8F45CE6FD7FC40D8BFF40D56FDCD4AE6');
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

    private function createRequest(): Request
    {
        $request = new Request([
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYER_ID => ConstantsForTesting::PAYER_ID_PAYMENT_PUI,
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_PAYMENT_ID => 'PAYID-LUWEJRI80B04311G7544303K',
        ]);

        return $request;
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
            new OrderTransactionStateHandler($this->orderTransactionRepo, $this->stateMachineRegistry),
            $this->orderTransactionRepo
        );
    }
}
