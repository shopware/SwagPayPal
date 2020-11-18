<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item\UnitAmount;
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
     * @return Item[]
     */
    public function getItemList(CurrencyEntity $currency, OrderEntity $order): array
    {
        $items = [];
        $currencyCode = $currency->getIsoCode();
        $lineItems = $order->getLineItems();
        if ($lineItems === null) {
            return [];
        }

        foreach ($lineItems as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $item = new Item();
            $item->setName($lineItem->getLabel());

            $unitAmount = new UnitAmount();
            $unitAmount->setCurrencyCode($currencyCode);
            $unitAmount->setValue($this->priceFormatter->formatPrice($price->getUnitPrice()));

            $item->setUnitAmount($unitAmount);
            $item->setQuantity($lineItem->getQuantity());

            $items[] = $item;
        }

        return $items;
    }
}
