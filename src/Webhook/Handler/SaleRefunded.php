<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Webhook\Exception\WebhookOrderTransactionNotFoundException;
use Swag\PayPal\Webhook\WebhookEventTypes;

class SaleRefunded extends AbstractWebhookHandler
{
    /**
     * {@inheritdoc}
     */
    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_SALE_REFUNDED;
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
            'stateId' => $this->getStateMachineState(OrderTransactionStates::STATE_REFUNDED, $context),
        ];
        $this->orderTransactionRepo->update([$data], $context);
    }
}
