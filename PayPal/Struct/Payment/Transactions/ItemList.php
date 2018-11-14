<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Struct\Payment\Transactions;

use SwagPayPal\PayPal\Struct\Payment\Transactions\ItemList\Item;
use SwagPayPal\PayPal\Struct\Payment\Transactions\ItemList\ShippingAddress;

class ItemList
{
    /**
     * @var Item[]
     */
    private $items;

    /**
     * @var ShippingAddress
     */
    private $shippingAddress;

    /**
     * @return Item[]
     */
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

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress($shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @param array[] $data
     */
    public static function fromArray(array $data): ItemList
    {
        $result = new self();

        $items = [];

        if (array_key_exists('items', $data)) {
            foreach ($data['items'] as $item) {
                $items[] = Item::fromArray($item);
            }
        }

        $result->setItems($items);
        $result->setShippingAddress(ShippingAddress::fromArray($data['shipping_address']));

        return $result;
    }

    public function toArray(): array
    {
        $result = [];

        /** @var Item $item */
        foreach ($this->getItems() as $item) {
            $result['items'][] = $item->toArray();
        }

        if ($this->getShippingAddress() !== null) {
            $result['shipping_address'] = $this->getShippingAddress()->toArray();
        }

        return $result;
    }
}
