<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Framework\Context;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Webhook\WebhookEventTypes;

class AuthorizationVoided extends AbstractWebhookHandler
{
    /**
     * {@inheritdoc}
     */
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_AUTHORIZATION_VOIDED;
    }

    /**
     * {@inheritdoc}
     */
    public function invoke(Webhook $webhook, Context $context): void
    {
        $orderTransaction = $this->getOrderTransaction($webhook, $context);

        $this->orderTransactionStateHandler->cancel($orderTransaction->getId(), $context);
    }
}
