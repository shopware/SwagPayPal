<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\AgentCommerce\Struct\V1;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @experimental
 */
#[Package('checkout')]
#[OA\Schema(
    schema: 'paypal_agentic_commerce_v1_shipping_option',
    required: ['price', 'isSelected']
)]
class ShippingOption extends PayPalApiStruct
{
    /**
     * Unique shipping option identifier
     */
    #[OA\Property(type: 'string')]
    protected ?string $id = null;

    /**
     * Display name
     */
    #[OA\Property(type: 'string')]
    protected ?string $name = null;

    /**
     * Detailed description
     */
    #[OA\Property(type: 'string')]
    protected ?string $description = null;

    #[OA\Property(ref: Money::class)]
    protected Money $price;

    /**
     * Whether this shipping option is currently selected
     */
    #[OA\Property(type: 'boolean')]
    protected bool $isSelected;

    /**
     * Estimated delivery date in YYYY-MM-DD format
     */
    #[OA\Property(
        type: 'string',
        pattern: '^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$'
    )]
    protected ?string $estimatedDelivery = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
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

    public function getPrice(): Money
    {
        return $this->price;
    }

    public function setPrice(Money $price): void
    {
        $this->price = $price;
    }

    public function isSelected(): bool
    {
        return $this->isSelected;
    }

    public function setIsSelected(bool $isSelected): void
    {
        $this->isSelected = $isSelected;
    }

    public function getEstimatedDelivery(): ?string
    {
        return $this->estimatedDelivery;
    }

    public function setEstimatedDelivery(?string $estimatedDelivery): void
    {
        $this->estimatedDelivery = $estimatedDelivery;
    }

    public function jsonSerialize(): array
    {
        return \array_filter(parent::jsonSerialize());
    }
}
