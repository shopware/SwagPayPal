<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class SaleComplete extends AbstractWebhookHandler
{
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_SALE_COMPLETED;
    }

    /**
     * @param WebhookV1 $webhook
     */
    public function invoke(PayPalApiStruct $webhook, Context $context): void
    {
        $orderTransaction = $this->getOrderTransaction($webhook, $context);

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_PAID)) {
            $this->orderTransactionStateHandler->paid($orderTransaction->getId(), $context);
        }
    }
}
