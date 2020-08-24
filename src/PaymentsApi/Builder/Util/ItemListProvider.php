<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\ItemList\Item;
use Swag\PayPal\Util\PriceFormatter;

class ItemListProvider
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * @throws InvalidOrderException
     *
     * @return Item[]
     */
    public function getItemList(
        OrderEntity $order,
        string $currency
    ): array {
        $items = [];
        $lineItems = $order->getLineItems();
        if ($lineItems === null) {
            throw new InvalidOrderException($order->getId());
        }

        foreach ($lineItems->getElements() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                return [];
            }

            $items[] = $this->createItemFromLineItem($lineItem, $currency, $price);
        }

        return $items;
    }

    private function createItemFromLineItem(
        OrderLineItemEntity $lineItem,
        string $currency,
        CalculatedPrice $price
    ): Item {
        $item = new Item();
        $item->setName($lineItem->getLabel());

        $payload = $lineItem->getPayload();
        if ($payload !== null && \array_key_exists('productNumber', $payload)) {
            $item->setSku($payload['productNumber']);
        }

        $item->setCurrency($currency);
        $item->setQuantity($lineItem->getQuantity());
        $item->setTax($this->priceFormatter->formatPrice(0));
        $item->setPrice($this->priceFormatter->formatPrice($price->getTotalPrice() / $lineItem->getQuantity()));

        return $item;
    }
}
