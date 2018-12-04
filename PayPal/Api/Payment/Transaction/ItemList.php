<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Api\Payment\Transaction;

use SwagPayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use SwagPayPal\PayPal\Api\Payment\Transaction\ItemList\ShippingAddress;
use SwagPayPal\PayPal\Api\PayPalStruct;

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
