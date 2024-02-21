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
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class AuthorizationVoided extends AbstractWebhookHandler
{
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        $resource = $webhook->getResource();
        $orderTransaction = match (true) {
            $resource instanceof Resource => $this->getOrderTransaction($resource, $context),
            $resource instanceof Authorization => $this->getOrderTransactionV2($resource, $context),
            default => null,
        };

        if ($orderTransaction === null) {
            throw new WebhookException($this->getEventType(), 'Order transaction could not be resolved');
        }

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_CANCELLED)) {
            $this->orderTransactionStateHandler->cancel($orderTransaction->getId(), $context);
        }
    }
}
