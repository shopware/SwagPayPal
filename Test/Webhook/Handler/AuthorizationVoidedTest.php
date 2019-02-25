<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\StateMachine\StateMachineRegistry;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use SwagPayPal\Webhook\Handler\AuthorizationVoided;
use SwagPayPal\Webhook\WebhookEventTypes;

class AuthorizationVoidedTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var AuthorizationVoided
     */
    private $webhookHandler;

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
        $webhook->assign(['resource' => ['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]]);
        $context = Context::createDefaultContext();
        $this->webhookHandler->invoke($webhook, $context);

        $result = $this->orderTransactionRepo->getData();

        $expectedStateId = $this->stateMachineRegistry->getStateByTechnicalName(
            Defaults::ORDER_TRANSACTION_STATE_MACHINE,
            Defaults::ORDER_TRANSACTION_STATES_CANCELLED,
            $context
        )->getId();

        static::assertSame(OrderTransactionRepoMock::ORDER_TRANSACTION_ID, $result['id']);
        static::assertSame($expectedStateId, $result['stateId']);
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
        return new AuthorizationVoided($this->orderTransactionRepo, $this->stateMachineRegistry);
    }
}
