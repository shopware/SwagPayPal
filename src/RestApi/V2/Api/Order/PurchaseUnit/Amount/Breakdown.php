<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Money;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_amount_breakdown')]
#[Package('checkout')]
class Breakdown extends PayPalApiStruct
{
    #[OA\Property(ref: Money::class)]
    protected Money $itemTotal;

    #[OA\Property(ref: Money::class)]
    protected Money $shipping;

    #[OA\Property(ref: Money::class)]
    protected Money $handling;

    #[OA\Property(ref: Money::class, nullable: true)]
    protected ?Money $taxTotal = null;

    #[OA\Property(ref: Money::class)]
    protected Money $insurance;

    #[OA\Property(ref: Money::class)]
    protected Money $shippingDiscount;

    #[OA\Property(ref: Money::class)]
    protected Money $discount;

    public function getItemTotal(): Money
    {
        return $this->itemTotal;
    }

    public function setItemTotal(Money $itemTotal): void
    {
        $this->itemTotal = $itemTotal;
    }

    public function getShipping(): Money
    {
        return $this->shipping;
    }

    public function setShipping(Money $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getHandling(): Money
    {
        return $this->handling;
    }

    public function setHandling(Money $handling): void
    {
        $this->handling = $handling;
    }

    public function getTaxTotal(): ?Money
    {
        return $this->taxTotal;
    }

    public function setTaxTotal(?Money $taxTotal): void
    {
        $this->taxTotal = $taxTotal;
    }

    public function getInsurance(): Money
    {
        return $this->insurance;
    }

    public function setInsurance(Money $insurance): void
    {
        $this->insurance = $insurance;
    }

    public function getShippingDiscount(): Money
    {
        return $this->shippingDiscount;
    }

    public function setShippingDiscount(Money $shippingDiscount): void
    {
        $this->shippingDiscount = $shippingDiscount;
    }

    public function getDiscount(): Money
    {
        return $this->discount;
    }

    public function setDiscount(Money $discount): void
    {
        $this->discount = $discount;
    }
}
