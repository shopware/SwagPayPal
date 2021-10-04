<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Handling;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Insurance;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ShippingDiscount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\TaxTotal;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_breakdown")
 */
class Breakdown extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected ItemTotal $itemTotal;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Shipping $shipping;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Handling $handling;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money", nullable=true)
     */
    protected ?TaxTotal $taxTotal = null;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Insurance $insurance;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected ShippingDiscount $shippingDiscount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Discount $discount;

    public function getItemTotal(): ItemTotal
    {
        return $this->itemTotal;
    }

    public function setItemTotal(ItemTotal $itemTotal): void
    {
        $this->itemTotal = $itemTotal;
    }

    public function getShipping(): Shipping
    {
        return $this->shipping;
    }

    public function setShipping(Shipping $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getHandling(): Handling
    {
        return $this->handling;
    }

    public function setHandling(Handling $handling): void
    {
        $this->handling = $handling;
    }

    public function getTaxTotal(): ?TaxTotal
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(?TaxTotal $taxTotal): void
    {
        $this->taxTotal = $taxTotal;
    }

    public function getInsurance(): Insurance
    {
        return $this->insurance;
    }

    public function setInsurance(Insurance $insurance): void
    {
        $this->insurance = $insurance;
    }

    public function getShippingDiscount(): ShippingDiscount
    {
        return $this->shippingDiscount;
    }

    public function setShippingDiscount(ShippingDiscount $shippingDiscount): void
    {
        $this->shippingDiscount = $shippingDiscount;
    }

    public function getDiscount(): Discount
    {
        return $this->discount;
    }

    public function setDiscount(Discount $discount): void
    {
        $this->discount = $discount;
    }
}
