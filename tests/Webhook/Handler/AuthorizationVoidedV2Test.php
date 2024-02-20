<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Handler\AuthorizationVoided;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class AuthorizationVoidedV2Test extends AbstractWebhookHandlerTestCase
{
    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED);
    }

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_AUTHORIZATION);
        $this->assertInvoke(OrderTransactionStates::STATE_CANCELLED, $webhook);
    }

    public function testInvokeWithoutResource(): void
    {
        $webhook = $this->createWebhookV2('no-valid-resource-type');
        $context = Context::createDefaultContext();

        $this->expectException(WebhookException::class);
        $this->expectExceptionMessage('Order transaction could not be resolved');
        $this->webhookHandler->invoke($webhook, $context);
    }

    public function testInvokeWithoutCustomId(): void
    {
        $this->assertInvokeWithoutCustomId(Webhook::RESOURCE_TYPE_AUTHORIZATION);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $orderTransactionId = Uuid::randomHex();
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_AUTHORIZATION, $orderTransactionId);
        $reason = \sprintf('with custom ID "%s" (order transaction ID)', $orderTransactionId);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED, $webhook, $reason);
    }

    public function testInvokeWithSameInitialState(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_AUTHORIZATION);
        $this->assertInvoke(OrderTransactionStates::STATE_CANCELLED, $webhook, OrderTransactionStates::STATE_CANCELLED);
    }

    protected function createWebhookHandler(): AuthorizationVoided
    {
        return new AuthorizationVoided(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry)
        );
    }
}
