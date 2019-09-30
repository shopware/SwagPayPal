<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\Handler\AuthorizationVoided;
use Swag\PayPal\Webhook\WebhookEventTypes;

class AuthorizationVoidedTest extends TestCase
{
    use KernelTestBehaviour;
    use StateMachineStateTrait;
    use OrderTransactionTrait;
    use DatabaseTransactionBehaviour;
    use OrderFixture;

    /**
     * @var AuthorizationVoided
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
     * @var DefinitionInstanceRegistry
     */
    private $definitionRegistry;

    protected function setUp(): void
    {
        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->getContainer()->get(DefinitionInstanceRegistry::class);
        $this->definitionRegistry = $definitionInstanceRegistry;
        $this->orderTransactionRepo = $this->definitionRegistry->getRepository(
            (new OrderTransactionDefinition())->getEntityName()
        );
        /** @var StateMachineRegistry $stateMachineRegistry */
        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->webhookHandler = $this->createWebhookHandler();
    }

    public function testGetEventType(): void
    {
        static::assertSame(WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED, $this->webhookHandler->getEventType());
    }

    public function testInvoke(): void
    {
        $webhook = new Webhook();
        $context = Context::createDefaultContext();
        $container = $this->getContainer();
        $transactionId = $this->getTransactionId($context, $container);
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]]);
        $this->webhookHandler->invoke($webhook, $context);

        $expectedStateId = $this->getOrderTransactionStateIdByTechnicalName(
            OrderTransactionStates::STATE_CANCELLED,
            $container,
            $context
        );

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);
        static::assertNotNull($expectedStateId);
        static::assertSame($expectedStateId, $transaction->getStateId());
    }

    public function testInvokeWithoutTransaction(): void
    {
        $webhook = new Webhook();
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION]]);
        $context = Context::createDefaultContext();

        $this->expectException(WebhookOrderTransactionNotFoundException::class);
        $this->expectExceptionMessage(
            sprintf(
                '[PayPal PAYMENT.AUTHORIZATION.VOIDED Webhook] Could not find associated order with the PayPal ID "%s"',
                OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION
            )
        );
        $this->webhookHandler->invoke($webhook, $context);
    }

    private function createWebhookHandler(): AuthorizationVoided
    {
        return new AuthorizationVoided(
            $this->definitionRegistry,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            new OrderTransactionDefinition()
        );
    }
}
