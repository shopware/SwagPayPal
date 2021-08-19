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

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $name;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var UnitAmount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected $unitAmount;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var Tax
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_common_money")
     */
    protected $tax;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var int
     * @OA\Property(type="integer")
     */
    protected $quantity;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string|null
     * @OA\Property(type="string", nullable=true)
     */
    protected $sku;

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
