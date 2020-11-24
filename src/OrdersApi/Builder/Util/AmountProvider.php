<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping as BreakdownShipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\Util\PriceFormatter;

class AmountProvider
{
    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    public function createAmount(
        CalculatedPrice $totalAmount,
        CalculatedPrice $shippingCosts,
        CurrencyEntity $currency,
        PurchaseUnit $purchaseUnit
    ): Amount {
        $currencyCode = $currency->getIsoCode();

        $amount = new Amount();
        $amount->setCurrencyCode($currencyCode);
        $amount->setValue($this->priceFormatter->formatPrice($totalAmount->getTotalPrice()));

        $items = $purchaseUnit->getItems();
        if ($items !== null) {
            // Only set breakdown if items are submitted, otherwise the breakdown will be invalid
            $amount->setBreakdown($this->createBreakdown($items, $purchaseUnit, $currencyCode, $shippingCosts));
        }

        return $amount;
    }

    /**
     * @param Item[] $items
     */
    private function createBreakdown(
        array $items,
        PurchaseUnit $purchaseUnit,
        string $currencyCode,
        CalculatedPrice $shippingCosts
    ): Breakdown {
        $itemTotalValue = 0.0;
        $discountValue = 0.0;
        foreach ($items as $key => $item) {
            $itemUnitAmount = (float) $item->getUnitAmount()->getValue();
            if ($itemUnitAmount < 0.0) {
                $discountValue += ($itemUnitAmount * -1);
                unset($items[$key]);
            } else {
                $itemTotalValue += $item->getQuantity() * $itemUnitAmount;
            }
        }
        $purchaseUnit->setItems($items);

        $itemTotal = new ItemTotal();
        $itemTotal->setCurrencyCode($currencyCode);
        $itemTotal->setValue($this->priceFormatter->formatPrice($itemTotalValue));

        $shipping = new BreakdownShipping();
        $shipping->setCurrencyCode($currencyCode);
        $shipping->setValue($this->priceFormatter->formatPrice($shippingCosts->getTotalPrice()));

        $discount = new Discount();
        $discount->setCurrencyCode($currencyCode);
        $discount->setValue($this->priceFormatter->formatPrice($discountValue));

        $breakdown = new Breakdown();
        $breakdown->setItemTotal($itemTotal);
        $breakdown->setShipping($shipping);
        $breakdown->setDiscount($discount);

        return $breakdown;
    }
}
