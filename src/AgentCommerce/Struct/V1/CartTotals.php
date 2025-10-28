<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @experimental
 *
 * Comprehensive cart pricing breakdown calculated by merchant and returned in all cart responses. All fields are merchant-owned and calculated based on business logic, inventory, shipping rules, and tax regulations.
 *
 * Merchant Responsibility:
 *
 * Calculate all totals based on items, shipping address, and business rules
 * Ensure accuracy for tax compliance and customer transparency
 * Handle currency consistency across all money fields
 * Apply discounts, shipping rates, and custom charges appropriately
 *
 * Field Calculation Guidelines:
 *
 * subtotal: Sum of all item prices × quantities (before discounts)
 * discount: Total amount saved from coupons, promotions, and discounts
 * shipping: Calculated shipping cost based on address and selected method
 * tax: Sales tax, VAT, or other applicable taxes based on billing/shipping jurisdiction
 * handling: Processing fees, packaging costs, or handling charges
 * insurance: Optional shipping insurance costs
 * shipping_discount: Discounts applied specifically to shipping costs
 * custom_charges: Additional fees like gift wrapping, expedited processing, etc.
 * total: Final amount customer pays (subtotal - discount + shipping + tax + handling + insurance - shipping_discount + custom_charges)
 *
 * PayPal Orders API Integration: When creating PayPal orders, custom_charges are typically rolled into the handling field or added as separate line items. The total amount must match the PayPal order total for successful payment capture. Fields map to PayPal Orders API breakdown structure where supported.
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_cart_totals',
    required: ['total']
)]
class CartTotals extends Struct
{
    #[OA\Property(ref: Money::class)]
    protected ?Money $subtotal = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $discount = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $shipping = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $tax = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $handling = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $insurance = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $shippingDiscount = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $customCharges = null;

    #[OA\Property(ref: Money::class)]
    protected Money $total;

    public function getSubtotal(): ?Money
    {
        return $this->subtotal;
    }

    public function setSubtotal(?Money $subtotal): void
    {
        $this->subtotal = $subtotal;
    }

    public function getDiscount(): ?Money
    {
        return $this->discount;
    }

    public function setDiscount(?Money $discount): void
    {
        $this->discount = $discount;
    }

    public function getShipping(): ?Money
    {
        return $this->shipping;
    }

    public function setShipping(?Money $shipping): void
    {
        $this->shipping = $shipping;
    }

    public function getTax(): ?Money
    {
        return $this->tax;
    }

    public function setTax(?Money $tax): void
    {
        $this->tax = $tax;
    }

    public function getHandling(): ?Money
    {
        return $this->handling;
    }

    public function setHandling(?Money $handling): void
    {
        $this->handling = $handling;
    }

    public function getInsurance(): ?Money
    {
        return $this->insurance;
    }

    public function setInsurance(?Money $insurance): void
    {
        $this->insurance = $insurance;
    }

    public function getShippingDiscount(): ?Money
    {
        return $this->shippingDiscount;
    }

    public function setShippingDiscount(?Money $shippingDiscount): void
    {
        $this->shippingDiscount = $shippingDiscount;
    }

    public function getCustomCharges(): ?Money
    {
        return $this->customCharges;
    }

    public function setCustomCharges(?Money $customCharges): void
    {
        $this->customCharges = $customCharges;
    }

    public function getTotal(): Money
    {
        return $this->total;
    }

    public function setTotal(Money $total): void
    {
        $this->total = $total;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
