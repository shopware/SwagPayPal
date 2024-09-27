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
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class CaptureRefunded extends AbstractWebhookHandler
{
    private PaymentStatusUtilV2 $paymentStatusUtil;

    private OrderResource $orderResource;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        PaymentStatusUtilV2 $paymentStatusUtil,
        OrderResource $orderResource,
    ) {
        parent::__construct($orderTransactionRepository, $orderTransactionStateHandler);
        $this->paymentStatusUtil = $paymentStatusUtil;
        $this->orderResource = $orderResource;
    }

    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_CAPTURE_REFUNDED;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        $refund = $webhook->getResource();
        if (!$refund instanceof Refund) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }
        $orderTransaction = $this->getOrderTransactionV2($refund, $context);

        $shopwareOrder = $orderTransaction->getOrder();
        if ($shopwareOrder === null) {
            throw new WebhookException($this->getEventType(), \sprintf('Order transaction with ID "%s" does not contain needed order', $orderTransaction->getId()));
        }

        $customFields = $orderTransaction->getCustomFields();
        if ($customFields === null) {
            throw new WebhookException($this->getEventType(), \sprintf('Order transaction with ID "%s" does not contain needed custom fields', $orderTransaction->getId()));
        }
        $paypalOrderId = $customFields[SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID];
        $paypalOrder = $this->orderResource->get($paypalOrderId, $shopwareOrder->getSalesChannelId());

        if ($this->isChangeAllowed($orderTransaction, OrderTransactionStates::STATE_REFUNDED)) {
            $this->paymentStatusUtil->applyRefundState($orderTransaction->getId(), $refund, $paypalOrder, $context);
        }
    }
}
