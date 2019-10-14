<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\ShippingAddress;
use Swag\PayPal\PayPal\Api\Payment\Transaction\ItemList\ShippingOption;

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
     * @var ShippingOption[]
     */
    protected $shippingOptions;

    /**
     * @var string
     */
    protected $shippingPhoneNumber;

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

    protected function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

    /**
     * @param ShippingOption[] $shippingOptions
     */
    protected function setShippingOptions(array $shippingOptions): void
    {
        $this->shippingOptions = $shippingOptions;
    }

    protected function setShippingPhoneNumber(string $shippingPhoneNumber): void
    {
        $this->shippingPhoneNumber = $shippingPhoneNumber;
    }
}
