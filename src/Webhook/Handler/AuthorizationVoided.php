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
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class AuthorizationVoided extends AbstractWebhookHandler
{
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED;
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    public function invoke(PayPalApiStruct $webhook, Context $context): void
    {
        if ($webhook instanceof WebhookV1) {
            $orderTransaction = $this->getOrderTransaction($webhook, $context);
        } else {
            /** @var Authorization|null $authorization */
            $authorization = $webhook->getResource();
            if ($authorization === null) {
                throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
            }
            $orderTransaction = $this->getOrderTransactionV2($authorization, $context);
        }

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_CANCELLED)) {
            $this->orderTransactionStateHandler->cancel($orderTransaction->getId(), $context);
        }
    }
}
