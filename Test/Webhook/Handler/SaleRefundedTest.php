<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook\Handler;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Exception\ResourceNotFoundException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionStateRepoMock;
use SwagPayPal\Webhook\Handler\SaleRefunded;
use SwagPayPal\Webhook\WebhookEventTypes;

class SaleRefundedTest extends TestCase
{
    /**
     * @var SaleRefunded
     */
    private $webhookHandler;

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    protected function setUp()
    {
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        $this->webhookHandler = $this->createWebhookHandler();
    }

    public function testGetEventType(): void
    {
        self::assertSame(WebhookEventTypes::PAYMENT_SALE_REFUNDED, $this->webhookHandler->getEventType());
    }

    public function testInvoke(): void
    {
        $webhook = new Webhook();
        $webhook->setResource(['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]);
        $context = Context::createDefaultContext();
        $this->webhookHandler->invoke($webhook, $context);

        $result = $this->orderTransactionRepo->getData();

        self::assertSame(OrderTransactionRepoMock::ORDER_TRANSACTION_ID, $result['id']);
        self::assertSame(OrderTransactionStateRepoMock::ORDER_TRANSACTION_STATE_ID, $result['orderTransactionStateId']);
    }

    public function testInvokeTransactionStateNotFound(): void
    {
        $webhook = new Webhook();
        $webhook->setResource(['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]);
        $context = Context::createDefaultContext();
        $context->addExtension(OrderTransactionStateRepoMock::NO_TRANSACTION_STATE_RESULT, new Entity());

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('The order_transaction_state resource with the following primary key was not found: position(14)');
        $this->webhookHandler->invoke($webhook, $context);
    }

    private function createWebhookHandler(): SaleRefunded
    {
        return new SaleRefunded(
            $this->orderTransactionRepo,
            new OrderTransactionStateRepoMock()
        );
    }
}
