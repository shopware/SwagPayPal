<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Shopware\PayPalSDK\Struct\V1\Webhook\Resource;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class SaleDenied extends AbstractWebhookHandler
{
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_SALE_DENIED;
    }

    public function invoke(Event $webhook, Context $context): void
    {
        if (!$webhook->getResource() instanceof Resource) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }

        $orderTransaction = $this->getOrderTransaction($webhook->getResource(), $context);

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_CANCELLED)) {
            $this->orderTransactionStateHandler->cancel($orderTransaction->getId(), $context);
        }
    }
}
