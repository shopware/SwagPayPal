<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping as BreakdownShipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\TaxTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\Util\PriceFormatter;

class AmountProvider
{
    private PriceFormatter $priceFormatter;

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
                    $totalAmount->getCalculatedTaxes(),
                    $isNet,
                    (float) $amount->getValue()
                )
            );

            if ($amount->getBreakdown() === null) {
                $purchaseUnit->setItems(null);
            }
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
        CalculatedTaxCollection $taxes,
        bool $isNet,
        float $amountValue
    ): ?Breakdown {
        $accumulatedAmountValue = 0.0;
        $itemTotalValue = 0.0;
        $discountValue = 0.0;
        $newItems = [];
        foreach ($items as $item) {
            $itemUnitAmount = (float) $item->getUnitAmount()->getValue();
            if ($itemUnitAmount < 0.0) {
                $discountValue += ($itemUnitAmount * -1);
            } else {
                $itemTotalValue += $item->getQuantity() * $itemUnitAmount;
                $newItems[] = $item;
            }
        }
        $purchaseUnit->setItems($newItems);

        $itemTotal = new ItemTotal();
        $itemTotal->setCurrencyCode($currencyCode);
        $itemTotal->setValue($this->priceFormatter->formatPrice($itemTotalValue));
        $accumulatedAmountValue += (float) $itemTotal->getValue();

        $shipping = new BreakdownShipping();
        $shipping->setCurrencyCode($currencyCode);
        $shipping->setValue($this->priceFormatter->formatPrice($shippingCosts->getTotalPrice()));
        $accumulatedAmountValue += (float) $shipping->getValue();

        $taxTotal = null;
        if ($isNet) {
            $taxTotal = new TaxTotal();
            $taxTotal->setCurrencyCode($currencyCode);
            $taxTotal->setValue($this->priceFormatter->formatPrice($taxes->getAmount()));
            $accumulatedAmountValue += (float) $taxTotal->getValue();
        }

        $discount = new Discount();
        $discount->setCurrencyCode($currencyCode);
        $discount->setValue($this->priceFormatter->formatPrice($discountValue));
        $accumulatedAmountValue -= (float) $discount->getValue();

        if ($accumulatedAmountValue !== $amountValue) {
            return null;
        }

        $breakdown = new Breakdown();
        $breakdown->setItemTotal($itemTotal);
        $breakdown->setShipping($shipping);
        $breakdown->setTaxTotal($taxTotal);
        $breakdown->setDiscount($discount);

        return $breakdown;
    }
}
