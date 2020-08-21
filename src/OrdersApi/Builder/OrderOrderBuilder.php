<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\PayPal\ApiV2\Api\Order;
use Swag\PayPal\PayPal\ApiV2\Api\Order\ApplicationContext;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Amount\Breakdown;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Amount\Breakdown\Discount;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Amount\Breakdown\ItemTotal;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Amount\Breakdown\Shipping as BreakdownShipping;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Item\UnitAmount;

class OrderOrderBuilder extends AbstractOrderBuilder
{
    public function getOrder(
        AsyncPaymentTransactionStruct $paymentTransaction,
        SalesChannelContext $salesChannelContext,
        CustomerEntity $customer
    ): Order {
        $settings = $this->settingsService->getSettings($salesChannelContext->getSalesChannel()->getId());

        $intent = $this->getIntent();
        $payer = $this->createPayer($customer);
        $purchaseUnit = $this->createPurchaseUnit($salesChannelContext, $paymentTransaction->getOrder(), $customer);
        $applicationContext = $this->createApplicationContext($salesChannelContext, $settings);
        $this->addReturnUrls($applicationContext, $paymentTransaction->getReturnUrl());

        $order = new Order();
        $order->setIntent($intent);
        $order->setPayer($payer);
        $order->setPurchaseUnits([$purchaseUnit]);
        $order->setApplicationContext($applicationContext);

        return $order;
    }

    private function createPurchaseUnit(
        SalesChannelContext $salesChannelContext,
        OrderEntity $order,
        CustomerEntity $customer
    ): PurchaseUnit {
        $currency = $salesChannelContext->getCurrency();
        $items = $this->createItems($currency, $order);
        $amount = $this->createAmount($currency, $order, $items);
        $shipping = $this->createShipping($customer);

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setAmount($amount);
        $purchaseUnit->setItems($items);
        $purchaseUnit->setShipping($shipping);

        return $purchaseUnit;
    }

    /**
     * @return Item[]
     */
    private function createItems(CurrencyEntity $currency, OrderEntity $order): array
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

    /**
     * @param Item[] $items
     */
    private function createAmount(CurrencyEntity $currency, OrderEntity $order, array $items): Amount
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
        $shipping->setValue($this->priceFormatter->formatPrice($order->getShippingCosts()->getTotalPrice()));

        $discount = new Discount();
        $discount->setCurrencyCode($currencyCode);
        $discount->setValue($this->priceFormatter->formatPrice($discountValue));

        $breakdown = new Breakdown();
        $breakdown->setItemTotal($itemTotal);
        $breakdown->setShipping($shipping);
        $breakdown->setDiscount($discount);

        $amount = new Amount();
        $amount->setCurrencyCode($currencyCode);
        $amount->setValue($this->priceFormatter->formatPrice($order->getPrice()->getTotalPrice()));
        $amount->setBreakdown($breakdown);

        return $amount;
    }

    private function addReturnUrls(ApplicationContext $applicationContext, string $returnUrl): void
    {
        $applicationContext->setReturnUrl($returnUrl);
        $applicationContext->setCancelUrl(\sprintf('%s&cancel=1', $returnUrl));
    }
}
