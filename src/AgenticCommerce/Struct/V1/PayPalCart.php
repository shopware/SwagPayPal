<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_pay_pal_cart',
    required: ['items', 'paymentMethod']
)]
class PayPalCart extends PayPalApiStruct
{
    public const STATUS__CREATED = 'CREATED';
    public const STATUS__INCOMPLETE = 'INCOMPLETE';
    public const STATUS__READY = 'READY';
    public const STATUS__COMPLETE = 'COMPLETE';

    /**
     * Cart data is complete and valid, ready for checkout
     *
     * All items are available with current pricing
     * Required information is complete (shipping address, customer data)
     * No business rule violations
     * validation_issues array is empty
     * Can proceed directly to checkout
     */
    public const VALIDATION_STATUS__VALID = 'VALID';

    /**
     * Cart has data issues that prevent checkout
     *
     * Items out of stock, price changes, business rule violations
     * Invalid or incomplete data that needs correction
     * validation_issues array contains specific problems
     * Customer/AI agent must resolve issues before checkout
     */
    public const VALIDATION_STATUS__INVALID = 'INVALID';

    /**
     * Cart needs more data but is otherwise valid
     *
     * Missing optional but required fields (shipping address, checkout fields)
     * Age verification, custom fields, delivery preferences needed
     * Items and pricing are valid, just needs customer input
     * validation_issues array indicates what information is needed
     */
    public const VALIDATION_STATUS__REQUIRES_ADDITIONAL_INFORMATION = 'REQUIRES_ADDITIONAL_INFORMATION';

    private const STATUSES = [self::STATUS__CREATED, self::STATUS__COMPLETE, self::STATUS__READY, self::STATUS__INCOMPLETE];
    private const VALIDATION_STATUSES = [self::VALIDATION_STATUS__VALID, self::VALIDATION_STATUS__INVALID, self::VALIDATION_STATUS__REQUIRES_ADDITIONAL_INFORMATION];

    #[OA\Property(type: 'string', readOnly: true)]
    protected string $id;

    #[OA\Property(
        type: 'string',
        enum: self::STATUSES,
        readOnly: true,
    )]
    protected string $status;

    #[OA\Property(
        type: 'string',
        enum: self::VALIDATION_STATUSES,
        readOnly: true,
    )]
    protected string $validationStatus;

    /**
     * List of issues preventing checkout (empty = ready)
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: ValidationIssue::class))]
    protected ValidationIssueCollection $validationIssues;

    #[OA\Property(ref: CartTotals::class)]
    protected ?CartTotals $totals = null;

    /**
     * Successfully applied coupons (server-calculated)
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: AppliedCoupon::class))]
    protected ?AppliedCouponCollection $appliedCoupons = null;

    /**
     * Available shipping methods with selection state
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: ShippingOption::class))]
    protected ?ShippingOptionCollection $availableShippingOptions = null;

    /**
     * HATEOAS navigation links for cart operations
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: Link::class))]
    protected ?LinkCollection $links = null;

    /**
     * Products in the cart
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: CartItem::class))]
    protected CartItemCollection $items;

    #[OA\Property(ref: Customer::class)]
    protected ?Customer $customer = null;

    #[OA\Property(ref: ShippingAddress::class)]
    protected ?ShippingAddress $shippingAddress = null;

    #[OA\Property(ref: BillingAddress::class)]
    protected ?BillingAddress $billingAddress = null;

    #[OA\Property(ref: PaymentMethod::class)]
    protected ?PaymentMethod $paymentMethod = null;

    /**
     * Custom checkout fields (age verification, etc.)
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: CheckoutField::class))]
    protected ?CheckoutFieldCollection $checkoutFields = null;

    /**
     * Discount coupons to apply or remove from cart
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: Coupon::class))]
    protected CouponCollection $coupons;

    #[OA\Property(ref: GeoCoordinates::class)]
    protected ?GeoCoordinates $geoCoordinates = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getValidationStatus(): string
    {
        return $this->validationStatus;
    }

    public function setValidationStatus(string $validationStatus): void
    {
        $this->validationStatus = $validationStatus;
    }

    public function getValidationIssues(): ValidationIssueCollection
    {
        return $this->validationIssues;
    }

    public function setValidationIssues(ValidationIssueCollection $validationIssues): void
    {
        $this->validationIssues = $validationIssues;
    }

    public function getAppliedCoupons(): ?AppliedCouponCollection
    {
        return $this->appliedCoupons;
    }

    public function setAppliedCoupons(?AppliedCouponCollection $appliedCoupons): void
    {
        $this->appliedCoupons = $appliedCoupons;
    }

    public function getTotals(): ?CartTotals
    {
        return $this->totals;
    }

    public function setTotals(?CartTotals $totals): void
    {
        $this->totals = $totals;
    }

    public function getAvailableShippingOptions(): ?ShippingOptionCollection
    {
        return $this->availableShippingOptions;
    }

    public function setAvailableShippingOptions(?ShippingOptionCollection $availableShippingOptions): void
    {
        $this->availableShippingOptions = $availableShippingOptions;
    }

    public function getLinks(): ?LinkCollection
    {
        return $this->links;
    }

    public function setLinks(?LinkCollection $links): void
    {
        $this->links = $links;
    }

    public function getItems(): CartItemCollection
    {
        return $this->items;
    }

    public function setItems(CartItemCollection $items): void
    {
        $this->items = $items;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function getShippingAddress(): ?ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    public function getBillingAddress(): ?BillingAddress
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?BillingAddress $billingAddress): void
    {
        $this->billingAddress = $billingAddress;
    }

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getCheckoutFields(): ?CheckoutFieldCollection
    {
        return $this->checkoutFields;
    }

    public function setCheckoutFields(?CheckoutFieldCollection $checkoutFields): void
    {
        $this->checkoutFields = $checkoutFields;
    }

    public function getCoupons(): CouponCollection
    {
        return $this->coupons;
    }

    public function setCoupons(CouponCollection $coupons): void
    {
        $this->coupons = $coupons;
    }

    public function getGeoCoordinates(): ?GeoCoordinates
    {
        return $this->geoCoordinates;
    }

    public function setGeoCoordinates(?GeoCoordinates $geoCoordinates): void
    {
        $this->geoCoordinates = $geoCoordinates;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
