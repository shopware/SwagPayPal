<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Swag\PayPal\Webhook\Handler\AuthorizationVoided;
use Swag\PayPal\Webhook\WebhookEventTypes;

class AuthorizationVoidedTest extends AbstractWebhookHandlerTestCase
{
    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED);
    }

    public function testInvoke(): void
    {
        $this->assertInvoke(OrderTransactionStates::STATE_CANCELLED);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED);
    }

    protected function createWebhookHandler(): AuthorizationVoided
    {
        return new AuthorizationVoided(
            $this->definitionRegistry,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            new OrderTransactionDefinition()
        );
    }
}
