<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgenticCommerce\Struct\V1\Context;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgenticCommerce\Struct\V1\Referral\MixedItem;
use Swag\PayPal\AgenticCommerce\Struct\V1\Referral\MixedItemCollection;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_context_pricing_error_context')]
class PricingErrorContext extends AbstractContext
{
    public const ISSUE__PRICE_MISMATCH = 'PRICE_MISMATCH';
    public const ISSUE__DISCOUNT_EXPIRED = 'DISCOUNT_EXPIRED';
    public const ISSUE__DISCOUNT_USAGE_LIMIT_EXCEEDED = 'DISCOUNT_USAGE_LIMIT_EXCEEDED';
    public const ISSUE__DISCOUNT_CUSTOMER_INELIGIBLE = 'DISCOUNT_CUSTOMER_INELIGIBLE';
    public const ISSUE__DISCOUNT_MINIMUM_NOT_MET = 'DISCOUNT_MINIMUM_NOT_MET';
    public const ISSUE__TAX_CALCULATION_FAILED = 'TAX_CALCULATION_FAILED';
    public const ISSUE__CURRENCY_NOT_SUPPORTED = 'CURRENCY_NOT_SUPPORTED';
    public const ISSUE__CURRENCY_MISMATCH = 'CURRENCY_MISMATCH';
    public const ISSUE__PROMOTIONAL_CONFLICT = 'PROMOTIONAL_CONFLICT';

    public const PRICE_CHANGE_REASON__PROMOTIONAL_ENDED = 'promotional_ended';
    public const PRICE_CHANGE_REASON__PROMOTIONAL_STARTED = 'promotional_started';
    public const PRICE_CHANGE_REASON__MARKET_ADJUSTMENT = 'market_adjustment';
    public const PRICE_CHANGE_REASON__COST_INCREASE = 'cost_increase';
    public const PRICE_CHANGE_REASON__SEASONAL_PRICING = 'seasonal_pricing';
    public const PRICE_CHANGE_REASON__COMPONENT_COST_INCREASE = 'component_cost_increase';
    public const PRICE_CHANGE_REASON__TERMS_UPDATED = 'terms_updated';

    private const PRICE_CHANGED_REASONS = [
        self::PRICE_CHANGE_REASON__PROMOTIONAL_ENDED,
        self::PRICE_CHANGE_REASON__PROMOTIONAL_STARTED,
        self::PRICE_CHANGE_REASON__MARKET_ADJUSTMENT,
        self::PRICE_CHANGE_REASON__COST_INCREASE,
        self::PRICE_CHANGE_REASON__SEASONAL_PRICING,
        self::PRICE_CHANGE_REASON__COMPONENT_COST_INCREASE,
        self::PRICE_CHANGE_REASON__TERMS_UPDATED,
    ];

    /**
     * Item with pricing issue
     */
    #[OA\Property(type: 'string')]
    protected ?string $itemId = null;

    /**
     * Original price value
     */
    #[OA\Property(type: 'string')]
    protected ?string $originalPrice = null;

    /**
     * Current price value
     */
    #[OA\Property(type: 'string')]
    protected ?string $currentPrice = null;

    /**
     * Currency code
     */
    #[OA\Property(type: 'string')]
    protected ?string $currencyCode = null;

    /**
     * Reason for price change
     */
    #[OA\Property(
        type: 'string',
        enum: self::PRICE_CHANGED_REASONS,
    )]
    protected ?string $priceChangeReason = null;

    /**
     * Amount of price increase
     */
    #[OA\Property(type: 'string')]
    protected ?string $priceIncrease = null;

    /**
     * Amount of price decrease
     */
    #[OA\Property(type: 'string')]
    protected ?string $priceDecrease = null;

    /**
     * Coupon code with issues
     */
    #[OA\Property(type: 'string')]
    protected ?string $couponCode = null;

    /**
     * Coupon usage limit
     */
    #[OA\Property(type: 'integer')]
    protected ?int $usageLimit = null;

    /**
     * Current coupon usage count
     */
    #[OA\Property(type: 'integer')]
    protected ?int $currentUsage = null;

    /**
     * Discount expiration date
     */
    #[OA\Property(type: 'string')]
    protected ?string $expirationDate = null;

    /**
     * Minimum order for discount
     */
    #[OA\Property(type: 'string')]
    protected ?string $minimumOrderAmount = null;

    /**
     * List of supported currencies
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $supportedCurrencies = null;

    /**
     * Multiple currencies found in cart
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $foundCurrencies = null;

    /**
     * Tax calculation service error
     */
    #[OA\Property(type: 'string')]
    protected ?string $taxServiceError = null;

    /**
     * Current system date for comparisons
     */
    #[OA\Property(type: 'string')]
    protected ?string $currentDate = null;

    /**
     * Discount amount that was applied
     */
    #[OA\Property(type: 'string')]
    protected ?string $discountAmount = null;

    /**
     * Whether all items must use same currency
     */
    #[OA\Property(type: 'boolean')]
    protected ?bool $requiredCurrencyConsistency = null;

    /**
     * Items with different currencies
     */
    #[OA\Property(type: 'array', items: new OA\Items(ref: MixedItem::class))]
    protected MixedItemCollection $mixedItems;

    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    public function setItemId(?string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getOriginalPrice(): ?string
    {
        return $this->originalPrice;
    }

    public function setOriginalPrice(?string $originalPrice): void
    {
        $this->originalPrice = $originalPrice;
    }

    public function getCurrentPrice(): ?string
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?string $currentPrice): void
    {
        $this->currentPrice = $currentPrice;
    }

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): void
    {
        $this->currencyCode = $currencyCode;
    }

    public function getPriceChangeReason(): ?string
    {
        return $this->priceChangeReason;
    }

    public function setPriceChangeReason(?string $priceChangeReason): void
    {
        if (!\in_array($priceChangeReason, self::PRICE_CHANGED_REASONS, true)) {
            throw new \InvalidArgumentException(\sprintf('Price change reason "%s" is not valid.', $priceChangeReason));
        }

        $this->priceChangeReason = $priceChangeReason;
    }

    public function getPriceIncrease(): ?string
    {
        return $this->priceIncrease;
    }

    public function setPriceIncrease(?string $priceIncrease): void
    {
        $this->priceIncrease = $priceIncrease;
    }

    public function getPriceDecrease(): ?string
    {
        return $this->priceDecrease;
    }

    public function setPriceDecrease(?string $priceDecrease): void
    {
        $this->priceDecrease = $priceDecrease;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): void
    {
        $this->couponCode = $couponCode;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function setUsageLimit(?int $usageLimit): void
    {
        $this->usageLimit = $usageLimit;
    }

    public function getCurrentUsage(): ?int
    {
        return $this->currentUsage;
    }

    public function setCurrentUsage(?int $currentUsage): void
    {
        $this->currentUsage = $currentUsage;
    }

    public function getExpirationDate(): ?string
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?string $expirationDate): void
    {
        $this->expirationDate = $expirationDate;
    }

    public function getMinimumOrderAmount(): ?string
    {
        return $this->minimumOrderAmount;
    }

    public function setMinimumOrderAmount(?string $minimumOrderAmount): void
    {
        $this->minimumOrderAmount = $minimumOrderAmount;
    }

    /**
     * @return ?string[]
     */
    public function getSupportedCurrencies(): ?array
    {
        return $this->supportedCurrencies;
    }

    /**
     * @param ?string[] $supportedCurrencies
     */
    public function setSupportedCurrencies(?array $supportedCurrencies): void
    {
        $this->supportedCurrencies = $supportedCurrencies;
    }

    public function addSupportedCurrency(string $supportedCurrency): void
    {
        $this->supportedCurrencies[] = $supportedCurrency;
    }

    /**
     * @return ?string[]
     */
    public function getFoundCurrencies(): ?array
    {
        return $this->foundCurrencies;
    }

    /**
     * @param ?string[] $foundCurrencies
     */
    public function setFoundCurrencies(?array $foundCurrencies): void
    {
        $this->foundCurrencies = $foundCurrencies;
    }

    public function addFoundCurrency(string $foundCurrency): void
    {
        $this->foundCurrencies[] = $foundCurrency;
    }

    public function getTaxServiceError(): ?string
    {
        return $this->taxServiceError;
    }

    public function setTaxServiceError(?string $taxServiceError): void
    {
        $this->taxServiceError = $taxServiceError;
    }

    public function getCurrentDate(): ?string
    {
        return $this->currentDate;
    }

    public function setCurrentDate(?string $currentDate): void
    {
        $this->currentDate = $currentDate;
    }

    public function getDiscountAmount(): ?string
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?string $discountAmount): void
    {
        $this->discountAmount = $discountAmount;
    }

    public function getRequiredCurrencyConsistency(): ?bool
    {
        return $this->requiredCurrencyConsistency;
    }

    public function setRequiredCurrencyConsistency(?bool $requiredCurrencyConsistency): void
    {
        $this->requiredCurrencyConsistency = $requiredCurrencyConsistency;
    }

    public function getMixedItems(): MixedItemCollection
    {
        return $this->mixedItems;
    }

    public function setMixedItems(MixedItemCollection $mixedItems): void
    {
        $this->mixedItems = $mixedItems;
    }

    protected static function getSpecificIssues(): array
    {
        return [
            self::ISSUE__PRICE_MISMATCH,
            self::ISSUE__DISCOUNT_EXPIRED,
            self::ISSUE__DISCOUNT_USAGE_LIMIT_EXCEEDED,
            self::ISSUE__DISCOUNT_CUSTOMER_INELIGIBLE,
            self::ISSUE__DISCOUNT_MINIMUM_NOT_MET,
            self::ISSUE__TAX_CALCULATION_FAILED,
            self::ISSUE__CURRENCY_NOT_SUPPORTED,
            self::ISSUE__CURRENCY_MISMATCH,
            self::ISSUE__PROMOTIONAL_CONFLICT,
        ];
    }
}
