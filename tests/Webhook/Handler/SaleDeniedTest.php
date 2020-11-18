<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Webhook\Handler\SaleDenied;
use Swag\PayPal\Webhook\WebhookEventTypes;

class SaleDeniedTest extends AbstractWebhookHandlerTestCase
{
    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_SALE_DENIED);
    }

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV1();
        $this->assertInvoke(OrderTransactionStates::STATE_CANCELLED, $webhook);
    }

    public function testInvokeWithoutParentPayment(): void
    {
        $this->assertInvokeWithoutParentPayment(WebhookEventTypes::PAYMENT_SALE_DENIED);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $webhook = $this->createWebhookV1(OrderTransactionRepoMock::WEBHOOK_WITHOUT_TRANSACTION);
        $reason = \sprintf('with the PayPal ID "%s"', OrderTransactionRepoMock::WEBHOOK_WITHOUT_TRANSACTION);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_SALE_DENIED, $webhook, $reason);
    }

    protected function createWebhookHandler(): SaleDenied
    {
        return new SaleDenied(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry)
        );
    }
}
