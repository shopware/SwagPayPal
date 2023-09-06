<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Handling;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping as BreakdownShipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\TaxTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\Util\PriceFormatter;

#[Package('checkout')]
class AmountProvider
{
    private PriceFormatter $priceFormatter;

    /**
     * @internal
     */
    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    public function createAmount(
        CalculatedPrice $totalAmount,
        CalculatedPrice $shippingCosts,
        CurrencyEntity $currency,
        PurchaseUnit $purchaseUnit,
        bool $isNet
    ): Amount {
        $currencyCode = $currency->getIsoCode();

        $amount = new Amount();
        $amount->setCurrencyCode($currencyCode);
        $amount->setValue($this->priceFormatter->formatPrice($totalAmount->getTotalPrice()));

        $items = $purchaseUnit->getItems();
        if ($items !== null) {
            // Only set breakdown if items are submitted, otherwise the breakdown will be invalid
            $amount->setBreakdown(
                $this->createBreakdown(
                    $items,
                    $purchaseUnit,
                    $currencyCode,
                    $shippingCosts,
                    $isNet,
                    (float) $amount->getValue()
                )
            );
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
        CalculatedPrice $shippingCosts,
        bool $isNet,
        float $amountValue
    ): Breakdown {
        $accumulatedAmountValue = 0.0;
        $accumulatedTaxValue = 0.0;
        $newItems = [];

        foreach ($items as $item) {
            $itemUnitAmount = (float) $item->getUnitAmount()->getValue();
            if ($itemUnitAmount >= 0.0) {
                $accumulatedAmountValue += $item->getQuantity() * $itemUnitAmount;
                $newItems[] = $item;
                $accumulatedTaxValue += $item->getQuantity() * (float) $item->getTax()->getValue();
            }
        }
        $purchaseUnit->setItems($newItems);

        $itemTotal = new ItemTotal();
        $itemTotal->setCurrencyCode($currencyCode);
        $itemTotal->setValue($this->priceFormatter->formatPrice($accumulatedAmountValue));

        $shipping = new BreakdownShipping();
        $shipping->setCurrencyCode($currencyCode);
        $shipping->setValue($this->priceFormatter->formatPrice($shippingCosts->getTotalPrice() + ($isNet ? $shippingCosts->getCalculatedTaxes()->getAmount() : 0.0)));
        $accumulatedAmountValue += (float) $shipping->getValue();

        $taxTotal = new TaxTotal();
        $taxTotal->setCurrencyCode($currencyCode);
        $taxTotal->setValue($this->priceFormatter->formatPrice($accumulatedTaxValue));
        $accumulatedAmountValue += (float) $taxTotal->getValue();

        $discount = new Discount();
        $discount->setCurrencyCode($currencyCode);
        $discount->setValue($this->priceFormatter->formatPrice($accumulatedAmountValue - $amountValue));

        $handling = new Handling();
        $handling->setCurrencyCode($currencyCode);
        // if due to rounding the order is more than the items, we add a fake handling fee
        if ((float) $discount->getValue() < 0.0) {
            $discount->setValue($this->priceFormatter->formatPrice(0.0));
            $handling->setValue($this->priceFormatter->formatPrice($amountValue - $accumulatedAmountValue));
        } else {
            $handling->setValue($this->priceFormatter->formatPrice(0.0));
        }

        $breakdown = new Breakdown();
        $breakdown->setItemTotal($itemTotal);
        $breakdown->setShipping($shipping);
        $breakdown->setTaxTotal($taxTotal);
        $breakdown->setDiscount($discount);
        $breakdown->setHandling($handling);

        return $breakdown;
    }
}
