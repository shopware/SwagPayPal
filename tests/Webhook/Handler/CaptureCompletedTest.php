<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Handler\CaptureCompleted;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class CaptureCompletedTest extends AbstractWebhookHandlerTestCase
{
    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_CAPTURE_COMPLETED);
    }

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke(OrderTransactionStates::STATE_PAID, $webhook);
    }

    public function testInvokeWithoutResource(): void
    {
        $this->assertInvokeWithoutResource();
    }

    public function testInvokeWithoutCustomId(): void
    {
        $this->assertInvokeWithoutCustomId(Webhook::RESOURCE_TYPE_CAPTURE);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $orderTransactionId = Uuid::randomHex();
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE, $orderTransactionId);
        $reason = \sprintf('with custom ID "%s" (order transaction ID)', $orderTransactionId);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_CAPTURE_COMPLETED, $webhook, $reason);
    }

    public function testInvokeWithSameInitialState(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke(OrderTransactionStates::STATE_PAID, $webhook, OrderTransactionStates::STATE_PAID);
    }

    protected function createWebhookHandler()
    {
        return new CaptureCompleted(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            $this->getContainer()->get(PaymentStatusUtilV2::class)
        );
    }
}
