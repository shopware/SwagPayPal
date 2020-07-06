<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Test\Customer\Rule\OrderFixture;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Swag\PayPal\PayPal\ApiV1\Api\Webhook;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\StateMachineStateTrait;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\Handler\AbstractWebhookHandler;

abstract class AbstractWebhookHandlerTestCase extends TestCase
{
    use KernelTestBehaviour;
    use StateMachineStateTrait;
    use DatabaseTransactionBehaviour;
    use OrderFixture;
    use OrderTransactionTrait;

    /**
     * @var StateMachineRegistry
     */
    protected $stateMachineRegistry;

    /**
     * @var DefinitionInstanceRegistry
     */
    protected $definitionRegistry;

    /**
     * @var AbstractWebhookHandler
     */
    private $webhookHandler;

    protected function setUp(): void
    {
        /** @var DefinitionInstanceRegistry $definitionInstanceRegistry */
        $definitionInstanceRegistry = $this->getContainer()->get(DefinitionInstanceRegistry::class);
        $this->definitionRegistry = $definitionInstanceRegistry;
        /** @var StateMachineRegistry $stateMachineRegistry */
        $stateMachineRegistry = $this->getContainer()->get(StateMachineRegistry::class);
        $this->stateMachineRegistry = $stateMachineRegistry;
        $this->webhookHandler = $this->createWebhookHandler();
    }

    protected function assertEventType(string $type): void
    {
        static::assertSame($type, $this->webhookHandler->getEventType());
    }

    protected function assertInvoke(
        string $expectedStateName,
        string $initialStateName = OrderTransactionStates::STATE_OPEN
    ): void {
        $webhook = new Webhook();
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]]);
        $context = Context::createDefaultContext();
        $container = $this->getContainer();
        $transactionId = $this->getTransactionId($context, $container, $initialStateName);
        $this->webhookHandler->invoke($webhook, $context);

        $expectedStateId = $this->getOrderTransactionStateIdByTechnicalName(
            $expectedStateName,
            $container,
            $context
        );

        $transaction = $this->getTransaction($transactionId, $container, $context);
        static::assertNotNull($transaction);
        static::assertNotNull($expectedStateId);
        static::assertSame($expectedStateId, $transaction->getStateId());
    }

    protected function assertInvokeWithoutTransaction(string $webhookName): void
    {
        $webhook = new Webhook();
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION]]);
        $context = Context::createDefaultContext();

        $this->expectException(WebhookOrderTransactionNotFoundException::class);
        $this->expectExceptionMessage(
            \sprintf(
                '[PayPal %s Webhook] Could not find associated order with the PayPal ID "%s"',
                $webhookName,
                OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID_WITHOUT_TRANSACTION
            )
        );
        $this->webhookHandler->invoke($webhook, $context);
    }

    /**
     * @return AbstractWebhookHandler
     */
    abstract protected function createWebhookHandler();
}
