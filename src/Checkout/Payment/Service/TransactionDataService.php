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
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order as PayPalOrder;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class TransactionDataService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $orderTransactionRepository,
        private readonly CredentialsUtilInterface $credentialsUtil,
    ) {
    }

    public function setOrderId(
        string $orderTransactionId,
        string $paypalOrderId,
        string $partnerAttributionId,
        string $salesChannelId,
        Context $context,
    ): void {
        $data = [
            'id' => $orderTransactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $paypalOrderId,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => $partnerAttributionId,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => $this->credentialsUtil->isSandbox($salesChannelId),
            ],
        ];
        $this->orderTransactionRepository->update([$data], $context);
    }

    public function setResourceId(PayPalOrder $order, string $transactionId, Context $context): void
    {
        $id = match ($order->getIntent()) {
            ConstantsV2::INTENT_CAPTURE => $order->getPurchaseUnits()->first()?->getPayments()?->getCaptures()?->first()?->getId(),
            ConstantsV2::INTENT_AUTHORIZE => $order->getPurchaseUnits()->first()?->getPayments()?->getAuthorizations()?->first()?->getId(),
            default => null,
        };

        if (!$id) {
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
