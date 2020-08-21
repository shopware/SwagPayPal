<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit;

use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Item\Tax;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Item\UnitAmount;
use Swag\PayPal\PayPal\PayPalApiStruct;

class Item extends PayPalApiStruct
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var UnitAmount
     */
    protected $unitAmount;

    /**
     * @var Tax
     */
    protected $tax;

    /**
     * @var int
     */
    protected $quantity;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
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
}
