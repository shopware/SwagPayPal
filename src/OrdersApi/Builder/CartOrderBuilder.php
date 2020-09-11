<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping as BreakdownShipping;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item\UnitAmount;

class CartOrderBuilder extends AbstractOrderBuilder
{
    public function getOrder(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Order {
        $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $intent = $this->getIntent();
        $payer = $this->createPayer($customer);
        $purchaseUnit = $this->createPurchaseUnit($salesChannelContext, $cart, $customer);
        $applicationContext = $this->createApplicationContext($salesChannelContext, $settings);

        $order = new Order();
        $order->setIntent($intent);
        $order->setPayer($payer);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);

        return $order;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        Cart $cart,
        CustomerEntity $customer
    ): PurchaseUnit {
        $currency = $salesChannelContext->getCurrency();
        $items = $this->createItems($currency, $cart);
        $amount = $this->createAmount($currency, $cart, $items);
        $shipping = $this->createShipping($customer);

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setAmount($amount);
        $purchaseUnit->setItems($items);
        $purchaseUnit->setShipping($shipping);

        return $purchaseUnit;
    }

    /**
     * @param Item[] $items
     */
    private function createAmount(CurrencyEntity $currency, Cart $cart, array &$items): Amount
    {
        $itemTotalValue = 0.0;
        $discountValue = 0.0;
        foreach ($items as $key => $item) {
            $itemUnitAmount = (float) $item->getUnitAmount()->getValue();
            if ($itemUnitAmount <= 0.0) {
                $discountValue += ($itemUnitAmount * -1);
                unset($items[$key]);
            } else {
                $itemTotalValue += $item->getQuantity() * $itemUnitAmount;
            }
        }

        $currencyCode = $currency->getIsoCode();

        $itemTotal = new ItemTotal();
        $itemTotal->setCurrencyCode($currencyCode);
        $itemTotal->setValue($this->priceFormatter->formatPrice($itemTotalValue));

        $shipping = new BreakdownShipping();
        $shipping->setCurrencyCode($currencyCode);
        $shipping->setValue($this->priceFormatter->formatPrice($cart->getShippingCosts()->getTotalPrice()));

        $discount = new Discount();
        $discount->setCurrencyCode($currencyCode);
        $discount->setValue($this->priceFormatter->formatPrice($discountValue));

        $breakdown = new Breakdown();
        $breakdown->setItemTotal($itemTotal);
        $breakdown->setShipping($shipping);
        $breakdown->setDiscount($discount);

        $amount = new Amount();
        $amount->setCurrencyCode($currencyCode);
        $amount->setValue($this->priceFormatter->formatPrice($cart->getPrice()->getTotalPrice()));
        $amount->setBreakdown($breakdown);

        return $amount;
    }

    /**
     * @return Item[]
     */
    private function createItems(CurrencyEntity $currency, Cart $cart): array
    {
        $items = [];
        $currencyCode = $currency->getIsoCode();

        foreach ($cart->getLineItems() as $lineItem) {
            $price = $lineItem->getPrice();

            if ($price === null) {
                continue;
            }

            $item = new Item();
            $item->setName((string) $lineItem->getLabel());

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
