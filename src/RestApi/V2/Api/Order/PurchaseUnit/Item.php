<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item\Tax;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item\UnitAmount;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_item")
 */
class Item extends PayPalApiStruct
{
    public const MAX_LENGTH_NAME = 127;
    public const MAX_LENGTH_SKU = 127;

    public const CATEGORY_PHYSICAL_GOODS = 'PHYSICAL_GOODS';
    public const CATEGORY_DIGITAL_GOODS = 'DIGITAL_GOODS';
    public const CATEGORY_DONATION = 'DONATION';

    /**
     * @OA\Property(type="string")
     */
    protected string $name;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected UnitAmount $unitAmount;

    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected Tax $tax;

    /**
     * @OA\Property(oneOf={"integer", "float", "string"})
     *
     * @var float|int|string
     */
    protected $taxRate;

    /**
     * @OA\Property(type="string")
     */
    protected string $category;

    /**
     * @OA\Property(type="integer")
     */
    protected int $quantity;

    /**
     * @OA\Property(type="string", nullable=true)
     */
    protected ?string $sku = null;

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setName(string $name): void
    {
        if (\mb_strlen($name) > self::MAX_LENGTH_NAME) {
            throw new \LengthException(
                \sprintf('%s::$name must not be longer than %s characters', self::class, self::MAX_LENGTH_NAME)
            );
        }

        $this->name = $name;
    }

    public function getUnitAmount(): UnitAmount
    {
        return $this->unitAmount;
    }

    public function setUnitAmount(UnitAmount $unitAmount): void
    {
        $this->unitAmount = $unitAmount;
    }

    public function getTax(): Tax
    {
        return $this->tax;
    }

    public function setTax(Tax $tax): void
    {
        $this->tax = $tax;
    }

    /**
     * @return string|int|float
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * @param string|int|float $taxRate
     */
    public function setTaxRate($taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @param int|string $quantity
     */
    public function setQuantity($quantity): void
    {
        $this->quantity = (int) $quantity;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    /**
     * @throws \LengthException if given parameter is too long
     */
    public function setSku(?string $sku): void
    {
        if ($sku !== null && \mb_strlen($sku) > self::MAX_LENGTH_SKU) {
            throw new \LengthException(
                \sprintf('%s::$sku must not be longer than %s characters', self::class, self::MAX_LENGTH_SKU)
            );
        }

        $this->sku = $sku;
    }
}
