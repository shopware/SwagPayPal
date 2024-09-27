<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V1\Api\Webhook\Resource;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class SaleRefunded extends AbstractWebhookHandler
{
    private SaleResource $saleResource;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        SaleResource $saleResource,
    ) {
        parent::__construct($orderTransactionRepository, $orderTransactionStateHandler);
        $this->saleResource = $saleResource;
    }

    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_SALE_REFUNDED;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        if (!$webhook->getResource() instanceof Resource) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }

        $orderTransaction = $this->getOrderTransaction($webhook->getResource(), $context);

        $order = $orderTransaction->getOrder();
        if (!$order) {
            return;
        }

        $sale = $this->saleResource->get($webhook->getResource()->getSaleId() ?? '', $order->getSalesChannelId());

        if ($sale->getState() === PaymentStatusV1::PAYMENT_PARTIALLY_REFUNDED) {
            if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_PARTIALLY_REFUNDED)) {
                $this->orderTransactionStateHandler->refundPartially($orderTransaction->getId(), $context);
            }

            return;
        }

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_REFUNDED)) {
            $this->orderTransactionStateHandler->refund($orderTransaction->getId(), $context);
        }
    }
}
