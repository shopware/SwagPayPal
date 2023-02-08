<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment\Transaction;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\ShippingAddress;
use Swag\PayPal\RestApi\V1\Api\Payment\Transaction\ItemList\ShippingOption;

/**
 * @OA\Schema(schema="swag_paypal_v1_payment_transaction_item_list")
 */
class ItemList extends PayPalApiStruct
{
    /**
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_payment_transaction_shipping_address")
     */
    protected ShippingAddress $shippingAddress;

    /**
     * @var Item[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_payment_transaction_item"})
     */
    protected array $items;

    /**
     * @var ShippingOption[]
     *
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v1_payment_transaction_shipping_option"})
     */
    protected array $shippingOptions;

    /**
     * @OA\Property(type="string")
     */
    protected string $shippingPhoneNumber;

    public function getShippingAddress(): ShippingAddress
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(ShippingAddress $shippingAddress): void
    {
        $this->shippingAddress = $shippingAddress;
    }

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

    /**
     * @return ShippingOption[]
     */
    public function getShippingOptions(): array
    {
        return $this->shippingOptions;
    }

    /**
     * @param ShippingOption[] $shippingOptions
     */
    public function setShippingOptions(array $shippingOptions): void
    {
        $this->shippingOptions = $shippingOptions;
    }

    public function getShippingPhoneNumber(): string
    {
        return $this->shippingPhoneNumber;
    }

    public function setShippingPhoneNumber(string $shippingPhoneNumber): void
    {
        $this->shippingPhoneNumber = $shippingPhoneNumber;
    }
}
