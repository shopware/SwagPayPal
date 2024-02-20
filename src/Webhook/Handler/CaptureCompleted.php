<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\WebhookEventTypes;

#[Package('checkout')]
class CaptureCompleted extends AbstractWebhookHandler
{
    private PaymentStatusUtilV2 $paymentStatusUtil;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $orderTransactionRepository,
        OrderTransactionStateHandler $orderTransactionStateHandler,
        PaymentStatusUtilV2 $paymentStatusUtil
    ) {
        parent::__construct($orderTransactionRepository, $orderTransactionStateHandler);
        $this->paymentStatusUtil = $paymentStatusUtil;
    }

    public function getEventType(): string
    {
        return WebhookEventTypes::PAYMENT_CAPTURE_COMPLETED;
    }

    public function invoke(Webhook $webhook, Context $context): void
    {
        $capture = $webhook->getResource();
        if (!$capture instanceof Capture) {
            throw new WebhookException($this->getEventType(), 'Given webhook does not have needed resource data');
        }
        $orderTransaction = $this->getOrderTransactionV2($capture, $context);

        $this->paymentStatusUtil->applyCaptureState($orderTransaction->getId(), $capture, $context);
    }
}
