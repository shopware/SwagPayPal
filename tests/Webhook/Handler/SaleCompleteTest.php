<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\Handler\SaleComplete;
use Swag\PayPal\Webhook\WebhookEventTypes;

class SaleCompleteTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var SaleComplete
     */
    private $webhookHandler;

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
        $this->webhookHandler = $this->createWebhookHandler();
    }

    public function testGetEventType(): void
    {
        static::assertSame(WebhookEventTypes::PAYMENT_SALE_COMPLETED, $this->webhookHandler->getEventType());
    }

    public function testInvoke(): void
    {
        $webhook = new Webhook();
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]]);
        $context = Context::createDefaultContext();
        $this->webhookHandler->invoke($webhook, $context);

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $result = $orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            OrderTransactionStates::STATE_MACHINE,
            OrderTransactionStates::STATE_PAID,
            $context
        )->getId();

        static::assertSame(OrderTransactionRepoMock::ORDER_TRANSACTION_ID, $result['id']);
        static::assertSame($expectedStateId, $result['stateId']);
    }

    private function createWebhookHandler(): SaleComplete
    {
        return new SaleComplete($this->definitionRegistry, $this->stateMachineRegistry);
    }
}
