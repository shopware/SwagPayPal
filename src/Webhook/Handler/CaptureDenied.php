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
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class CaptureDenied extends AbstractWebhookHandler
{
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_CAPTURE_DENIED;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        $capture = $webhook->getResource();
        if (!$capture instanceof Capture) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }
        $orderTransaction = $this->getOrderTransactionV2($capture, $context);

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_CANCELLED)) {
            $this->orderTransactionStateHandler->cancel($orderTransaction->getId(), $context);
        }
    }
}
