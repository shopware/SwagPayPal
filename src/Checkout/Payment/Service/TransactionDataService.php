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
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Order as PayPalOrder;
use Swag\PayPal\RestApi\V2\PaymentIntentV2;
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
        SalesChannelContext $salesChannelContext,
    ): void {
        $data = [
            'id' => $orderTransactionId,
            'customFields' => [
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => $paypalOrderId,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_PARTNER_ATTRIBUTION_ID => $partnerAttributionId,
                SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_IS_SANDBOX => $this->credentialsUtil->isSandbox($salesChannelContext->getSalesChannelId()),
            ],
        ];
        $this->orderTransactionRepository->update([$data], $salesChannelContext->getContext());
    }

    public function setResourceId(PayPalOrder $order, string $transactionId, Context $context): void
    {
        $id = match ($order->getIntent()) {
            PaymentIntentV2::CAPTURE => $order->getPurchaseUnits()->first()?->getPayments()?->getCaptures()?->first()?->getId(),
            PaymentIntentV2::AUTHORIZE => $order->getPurchaseUnits()->first()?->getPayments()?->getAuthorizations()?->first()?->getId(),
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
