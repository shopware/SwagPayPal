<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;

class Item extends PayPalStruct
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @var string
     */
    protected $price;

    /**
     * @var int
     */
    protected $quantity;

    /**
     * @var string
     */
    protected $sku;

    /**
     * @var string
     */
    protected $tax;

    public function getPrice(): string
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTax(): string
    {
        return $this->tax;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function setPrice(string $price): void
    {
        $this->price = $price;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }

    public function setTax(string $tax): void
    {
        $this->tax = $tax;
    }
}
