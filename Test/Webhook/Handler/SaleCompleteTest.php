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
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Webhook\Handler\SaleComplete;
use SwagPayPal\Webhook\WebhookEventTypes;

class SaleCompleteTest extends TestCase
{
    /**
     * @var SaleComplete
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
        self::assertSame(WebhookEventTypes::PAYMENT_SALE_COMPLETED, $this->webhookHandler->getEventType());
    }

    public function testInvoke(): void
    {
        $webhook = new Webhook();
        $webhook->setResource(['parent_payment' => OrderTransactionRepoMock::WEBHOOK_PAYMENT_ID]);
        $context = Context::createDefaultContext();
        $this->webhookHandler->invoke($webhook, $context);

        $result = $this->orderTransactionRepo->getData();

        self::assertSame(OrderTransactionRepoMock::ORDER_TRANSACTION_ID, $result['id']);
        self::assertSame(Defaults::ORDER_TRANSACTION_COMPLETED, $result['orderTransactionStateId']);
    }

    private function createWebhookHandler(): SaleComplete
    {
        return new SaleComplete($this->orderTransactionRepo);
    }
}
