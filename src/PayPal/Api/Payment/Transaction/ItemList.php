<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Api\Payment\Transaction;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\ShippingAddress;

class ItemList extends PayPalStruct
{
    /**
     * @var ShippingAddress
     */
    protected $shippingAddress;

    /**
     * @var Item[]
     */
    protected $items;

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param Item[] $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    protected function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }
}
