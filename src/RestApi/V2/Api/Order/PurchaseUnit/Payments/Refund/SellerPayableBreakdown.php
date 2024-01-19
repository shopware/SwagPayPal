<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Money;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_refund_seller_paypable_breakdown")
 */
#[Package('checkout')]
class SellerPayableBreakdown extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Money $grossAmount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Money $paypalFee;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Money $netAmount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Money $totalRefundedAmount;

    public function getGrossAmount(): Money
    {
        return $this->grossAmount;
    }

    public function setGrossAmount(Money $grossAmount): void
    {
        $this->grossAmount = $grossAmount;
    }

    public function getPaypalFee(): Money
    {
        return $this->paypalFee;
    }

    public function setPaypalFee(Money $paypalFee): void
    {
        $this->paypalFee = $paypalFee;
    }

    public function getNetAmount(): Money
    {
        return $this->netAmount;
    }

    public function setNetAmount(Money $netAmount): void
    {
        $this->netAmount = $netAmount;
    }

    public function getTotalRefundedAmount(): Money
    {
        return $this->totalRefundedAmount;
    }

    public function setTotalRefundedAmount(Money $totalRefundedAmount): void
    {
        $this->totalRefundedAmount = $totalRefundedAmount;
    }
}
