<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class TransactionDataService
{
    private EntityRepository $orderTransactionRepository;

    /**
     * @internal
     */
    public function __construct(EntityRepository $orderTransactionRepository)
    {
        $this->orderTransactionRepository = $orderTransactionRepository;
    }

    public function setOrderId(
        string $orderTransactionId,
        string $paypalOrderId,
        string $partnerAttributionId,
        Context $context
    ): void {
        $data = [
            'id' => $orderTransactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $paypalOrderId,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => $partnerAttributionId,
            ],
        ];
        $this->orderTransactionRepository->update([$data], $context);
    }

    public function setResourceId(PayPalOrder $order, string $transactionId, Context $context): void
    {
        $payments = $order->getPurchaseUnits()->first()?->getPayments();
        if ($payments === null) {
            return;
        }

        $captures = $payments->getCaptures();
        $authorizations = $payments->getAuthorizations();

        $id = null;
        if ($captures && $order->getIntent() === PaymentIntentV2::CAPTURE) {
            $id = $captures->first()?->getId();
        } elseif ($authorizations && $order->getIntent() === PaymentIntentV2::AUTHORIZE) {
            $id = $authorizations->first()?->getId();
        }

        if ($id === null) {
            return;
        }

        $this->orderTransactionRepository->update([[
            'id' => $transactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_RESOURCE_ID => $id,
            ],
        ]], $context);
    }
}
