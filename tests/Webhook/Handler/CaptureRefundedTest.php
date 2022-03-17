<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V2\Api\Webhook;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Handler\CaptureRefunded;
use Swag\PayPal\Webhook\WebhookEventTypes;

class CaptureRefundedTest extends AbstractWebhookHandlerTestCase
{
    use ServicesTrait;

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_REFUND);
        $this->assertInvoke(OrderTransactionStates::STATE_PARTIALLY_REFUNDED, $webhook, OrderTransactionStates::STATE_PAID);
    }

    public function testInvokeWithoutResource(): void
    {
        $this->assertInvokeWithoutResource();
    }

    public function testInvokeWithoutCustomId(): void
    {
        $this->assertInvokeWithoutCustomId(Webhook::RESOURCE_TYPE_REFUND);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $orderTransactionId = Uuid::randomHex();
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_REFUND, $orderTransactionId);
        $reason = \sprintf('with custom ID "%s" (order transaction ID)', $orderTransactionId);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_CAPTURE_REFUNDED, $webhook, $reason);
    }

    public function testInvokeWithSameInitialState(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_REFUND);
        $this->assertInvoke(OrderTransactionStates::STATE_PARTIALLY_REFUNDED, $webhook, OrderTransactionStates::STATE_PARTIALLY_REFUNDED);
    }

    protected function createWebhookHandler(): CaptureRefunded
    {
        return new CaptureRefunded(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            $this->getContainer()->get(PaymentStatusUtilV2::class),
            $this->createOrderResource($this->createDefaultSystemConfig())
        );
    }
}
