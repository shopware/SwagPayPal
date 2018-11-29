<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook\Handler;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use SwagPayPal\Webhook\WebhookEventTypes;

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
     *
     * @throws WebhookOrderTransactionNotFoundException
     */
    public function invoke(Webhook $webhook, Context $context): void
    {
        $orderTransaction = $this->getOrderTransaction($webhook, $context);

        $data = [
            'id' => $orderTransaction->getId(),
            'orderTransactionStateId' => Defaults::ORDER_TRANSACTION_FAILED,
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }
}
