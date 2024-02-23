<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Common\Money;

#[OA\Schema(schema: 'swag_paypal_v2_order_purchase_unit_item')]
#[Package('checkout')]
class Item extends PayPalApiStruct
{
    public const MAX_LENGTH_NAME = 120;
    public const MAX_LENGTH_SKU = 127;

    public const CATEGORY_PHYSICAL_GOODS = 'PHYSICAL_GOODS';
    public const CATEGORY_DIGITAL_GOODS = 'DIGITAL_GOODS';
    public const CATEGORY_DONATION = 'DONATION';

    #[OA\Property(type: 'string')]
    protected string $name;

    #[OA\Property(ref: Money::class)]
    protected Money $unitAmount;

    #[OA\Property(ref: Money::class)]
    protected Money $tax;

    #[OA\Property(oneOf: [new OA\Schema(type: 'string'), new OA\Schema(type: 'integer'), new OA\Schema(type: 'float')])]
    protected string|int|float $taxRate;

    #[OA\Property(type: 'string', enum: [self::CATEGORY_PHYSICAL_GOODS, self::CATEGORY_DIGITAL_GOODS, self::CATEGORY_DONATION])]
    protected string $category;

    #[OA\Property(type: 'integer')]
    protected int $quantity;

    #[OA\Property(type: 'string', nullable: true)]
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

    public function getUnitAmount(): Money
    {
        return $this->unitAmount;
    }

    public function setUnitAmount(Money $unitAmount): void
    {
        $this->unitAmount = $unitAmount;
    }

    public function getTax(): Money
    {
        return $this->tax;
    }

    public function setTax(Money $tax): void
    {
        $this->tax = $tax;
    }

    public function getTaxRate(): string|int|float
    {
        return $this->taxRate;
    }

    public function setTaxRate(string|int|float $taxRate): void
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

    public function setQuantity(int|string $quantity): void
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
