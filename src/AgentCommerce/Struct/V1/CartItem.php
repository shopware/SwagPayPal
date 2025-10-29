<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\CustomOption;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\CustomOptionCollection;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\SelectedAttribute;
use Swag\PayPal\AgentCommerce\Struct\V1\Referral\SelectedAttributeCollection;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_cart_item',
    required: ['itemId', 'quantity', 'price']
)]
class CartItem extends PayPalApiStruct
{
    /**
     * Unique product identifier (optional in v1 for backwards compatibility)
     */
    #[OA\Property(type: 'string')]
    protected ?string $itemId = null;

    /**
     * Product variant identifier (color, size, etc.) - unique id of the product
     */
    #[OA\Property(type: 'string')]
    protected ?string $variantId = null;

    /**
     * Item grouping identifier - passed when item is part of a group in honey catalog
     */
    #[OA\Property(type: 'string')]
    protected ?string $parentId = null;

    /**
     * Number of items
     */
    #[OA\Property(type: 'integer', minimum: 1)]
    protected int $quantity;

    /**
     * Product display name
     */
    #[OA\Property(type: 'string')]
    protected ?string $name = null;

    /**
     * Product description
     */
    #[OA\Property(type: 'string')]
    protected ?string $description = null;

    /**
     * URL for product details page
     */
    #[OA\Property(type: 'string')]
    protected ?string $itemUrl = null;

    #[OA\Property(ref: Money::class)]
    protected ?Money $price = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: SelectedAttribute::class))]
    protected SelectedAttributeCollection $selectedAttributes;

    #[OA\Property(ref: GiftOptions::class)]
    protected ?GiftOptions $giftOptions = null;

    #[OA\Property(type: 'array', items: new OA\Items(ref: CustomOption::class))]
    protected CustomOptionCollection $customOptions;

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

    public function getParentId(): ?string
    {
        return $this->parentId;
    }

    public function setParentId(?string $parentId): void
    {
        $this->parentId = $parentId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getItemUrl(): ?string
    {
        return $this->itemUrl;
    }

    public function setItemUrl(?string $itemUrl): void
    {
        $this->itemUrl = $itemUrl;
    }

    public function getPrice(): ?Money
    {
        return $this->price;
    }

    public function setPrice(?Money $price): void
    {
        $this->price = $price;
    }

    public function getSelectedAttributes(): SelectedAttributeCollection
    {
        return $this->selectedAttributes;
    }

    public function setSelectedAttributes(SelectedAttributeCollection $selectedAttributes): void
    {
        $this->selectedAttributes = $selectedAttributes;
    }

    public function getGiftOptions(): ?GiftOptions
    {
        return $this->giftOptions;
    }

    public function setGiftOptions(?GiftOptions $giftOptions): void
    {
        $this->giftOptions = $giftOptions;
    }

    public function getCustomOptions(): CustomOptionCollection
    {
        return $this->customOptions;
    }

    public function setCustomOptions(CustomOptionCollection $customOptions): void
    {
        $this->customOptions = $customOptions;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
