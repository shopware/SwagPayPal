<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1\Context;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(schema: 'paypal_agentic_commerce_v1_context_inventory_issue_context')]
class InventoryIssueContext extends AbstractContext
{
    public const ISSUE__ITEM_OUT_OF_STOCK = 'ITEM_OUT_OF_STOCK';
    public const ISSUE__INSUFFICIENT_INVENTORY = 'INSUFFICIENT_INVENTORY';
    public const ISSUE__BACK_ORDERED = 'BACK_ORDERED';
    public const ISSUE__PRE_ORDER_ONLY = 'PRE_ORDER_ONLY';
    public const ISSUE__ITEM_DISCONTINUED = 'ITEM_DISCONTINUED';
    public const ISSUE__LOW_STOCK_WARNING = 'LOW_STOCK_WARNING';
    public const ISSUE__INVENTORY_RESERVED = 'INVENTORY_RESERVED';
    public const ISSUE__SEASONAL_UNAVAILABLE = 'SEASONAL_UNAVAILABLE';
    public const ISSUE__VARIANT_NOT_AVAILABLE = 'VARIANT_NOT_AVAILABLE';
    public const ISSUE__CUSTOM_OPTION_UNAVAILABLE = 'CUSTOM_OPTION_UNAVAILABLE';

    /**
     * Product item identifier
     */
    #[OA\Property(type: 'string')]
    protected ?string $itemId = null;

    /**
     * Product variant identifier if applicable
     */
    #[OA\Property(type: 'string')]
    protected ?string $variantId = null;

    /**
     * Currently available quantity
     */
    #[OA\Property(
        type: 'integer',
        minimum: 0,
    )]
    protected ?int $availableQuantity = null;

    #[OA\Property(
        type: 'integer',
        minimum: 1,
    )]
    protected ?int $requestedQuantity = null;

    /**
     * Quantity reserved for other transactions
     */
    #[OA\Property(
        type: 'integer',
        minimum: 0,
    )]
    protected ?int $reservedQuantity = null;

    /**
     * Expected restock date
     */
    #[OA\Property(type: 'string')]
    protected ?string $restockDate = null;

    /**
     * Estimated shipping date for back-orders
     */
    #[OA\Property(type: 'string')]
    protected ?string $estimatedShipDate = null;

    /**
     * Maximum allowed back-order quantity
     */
    #[OA\Property(type: 'integer')]
    protected ?int $backOrderLimit = null;

    #[OA\Property(type: 'integer')]
    protected ?int $currentBackOrders = null;

    #[OA\Property(type: 'string')]
    protected ?string $discontinuationDate = null;

    /**
     * Alternative product IDs
     *
     * @var string[]
     */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'string'),
    )]
    protected ?array $suggestedAlternatives = null;

    /**
     * Whether newer version is available
     */
    #[OA\Property(type: 'boolean')]
    protected ?bool $upgradeAvailable = null;

    /**
     * When seasonal product becomes available
     */
    #[OA\Property(type: 'string')]
    protected ?string $seasonalStartDate = null;

    /**
     * When item was last sold
     */
    #[OA\Property(type: 'string')]
    protected ?string $lastSold = null;

    public function getItemId(): ?string
    {
        return $this->itemId;
    }

    public function setItemId(?string $itemId): void
    {
        $this->itemId = $itemId;
    }

    public function getVariantId(): ?string
    {
        return $this->variantId;
    }

    public function setVariantId(?string $variantId): void
    {
        $this->variantId = $variantId;
    }

    public function getAvailableQuantity(): ?int
    {
        return $this->availableQuantity;
    }

    public function setAvailableQuantity(?int $availableQuantity): void
    {
        if ($availableQuantity < 0) {
            $availableQuantity = 0;
        }

        $this->availableQuantity = $availableQuantity;
    }

    public function getRequestedQuantity(): ?int
    {
        return $this->requestedQuantity;
    }

    public function setRequestedQuantity(?int $requestedQuantity): void
    {
        if ($requestedQuantity < 1) {
            $requestedQuantity = 1;
        }

        $this->requestedQuantity = $requestedQuantity;
    }

    public function getReservedQuantity(): ?int
    {
        return $this->reservedQuantity;
    }

    public function setReservedQuantity(?int $reservedQuantity): void
    {
        if ($reservedQuantity < 0) {
            $reservedQuantity = 0;
        }

        $this->reservedQuantity = $reservedQuantity;
    }

    public function getRestockDate(): ?string
    {
        return $this->restockDate;
    }

    public function setRestockDate(?string $restockDate): void
    {
        $this->restockDate = $restockDate;
    }

    public function getEstimatedShipDate(): ?string
    {
        return $this->estimatedShipDate;
    }

    public function setEstimatedShipDate(?string $estimatedShipDate): void
    {
        $this->estimatedShipDate = $estimatedShipDate;
    }

    public function getBackOrderLimit(): ?int
    {
        return $this->backOrderLimit;
    }

    public function setBackOrderLimit(?int $backOrderLimit): void
    {
        $this->backOrderLimit = $backOrderLimit;
    }

    public function getCurrentBackOrders(): ?int
    {
        return $this->currentBackOrders;
    }

    public function setCurrentBackOrders(?int $currentBackOrders): void
    {
        $this->currentBackOrders = $currentBackOrders;
    }

    public function getDiscontinuationDate(): ?string
    {
        return $this->discontinuationDate;
    }

    public function setDiscontinuationDate(?string $discontinuationDate): void
    {
        $this->discontinuationDate = $discontinuationDate;
    }

    /**
     * @return ?string[]
     */
    public function getSuggestedAlternatives(): ?array
    {
        return $this->suggestedAlternatives;
    }

    /**
     * @param ?string[] $suggestedAlternatives
     */
    public function setSuggestedAlternatives(?array $suggestedAlternatives): void
    {
        $this->suggestedAlternatives = $suggestedAlternatives;
    }

    public function addSuggestedAlternative(string $suggestedAlternative): void
    {
        $this->suggestedAlternatives[] = $suggestedAlternative;
    }

    public function getUpgradeAvailable(): ?bool
    {
        return $this->upgradeAvailable;
    }

    public function setUpgradeAvailable(?bool $upgradeAvailable): void
    {
        $this->upgradeAvailable = $upgradeAvailable;
    }

    public function getSeasonalStartDate(): ?string
    {
        return $this->seasonalStartDate;
    }

    public function setSeasonalStartDate(?string $seasonalStartDate): void
    {
        $this->seasonalStartDate = $seasonalStartDate;
    }

    public function getLastSold(): ?string
    {
        return $this->lastSold;
    }

    public function setLastSold(?string $lastSold): void
    {
        $this->lastSold = $lastSold;
    }

    protected static function getSpecificIssues(): array
    {
        return [
            self::ISSUE__ITEM_OUT_OF_STOCK,
            self::ISSUE__INSUFFICIENT_INVENTORY,
            self::ISSUE__BACK_ORDERED,
            self::ISSUE__PRE_ORDER_ONLY,
            self::ISSUE__ITEM_DISCONTINUED,
            self::ISSUE__LOW_STOCK_WARNING,
            self::ISSUE__INVENTORY_RESERVED,
            self::ISSUE__SEASONAL_UNAVAILABLE,
            self::ISSUE__VARIANT_NOT_AVAILABLE,
            self::ISSUE__CUSTOM_OPTION_UNAVAILABLE,
        ];
    }
}
