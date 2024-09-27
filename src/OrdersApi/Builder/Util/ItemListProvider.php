<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Builder\Util;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\State;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\OrdersApi\Builder\Event\PayPalV2ItemFromCartEvent;
use Swag\PayPal\OrdersApi\Builder\Event\PayPalV2ItemFromOrderEvent;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ItemCollection;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Package('checkout')]
class ItemListProvider
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PriceFormatter $priceFormatter,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getItemList(CurrencyEntity $currency, OrderEntity $order): ItemCollection
    {
        $items = new ItemCollection();
        $currencyCode = $currency->getIsoCode();
        $isNet = $order->getTaxStatus() !== CartPrice::TAX_STATE_GROSS; /* @phpstan-ignore-line */
        $lineItems = $order->getNestedLineItems();
        if ($lineItems === null) {
            return new ItemCollection();
        }

        foreach ($lineItems as $lineItem) {
            $item = new Item();
            $this->setName($lineItem, $item);
            $this->setSku($lineItem, $item);
            $item->setCategory(\in_array(State::IS_DOWNLOAD, $lineItem->getStates(), true) ? Item::CATEGORY_DIGITAL_GOODS : Item::CATEGORY_PHYSICAL_GOODS);
            $this->buildPriceData($lineItem, $item, $currencyCode, $isNet);

            $event = new PayPalV2ItemFromOrderEvent($item, $lineItem);
            $this->eventDispatcher->dispatch($event);

            $items->add($event->getPayPalLineItem());
        }

        return $items;
    }

    public function getItemListFromCart(CurrencyEntity $currency, Cart $cart): ItemCollection
    {
        $items = new ItemCollection();
        $currencyCode = $currency->getIsoCode();
        $isNet = $cart->getPrice()->getTaxStatus() !== CartPrice::TAX_STATE_GROSS;

        foreach ($cart->getLineItems() as $lineItem) {
            $item = new Item();
            $this->setName($lineItem, $item);
            $this->setSku($lineItem, $item);
            $item->setCategory(\in_array(State::IS_DOWNLOAD, $lineItem->getStates(), true) ? Item::CATEGORY_DIGITAL_GOODS : Item::CATEGORY_PHYSICAL_GOODS);
            $this->buildPriceData($lineItem, $item, $currencyCode, $isNet);

            $event = new PayPalV2ItemFromCartEvent($item, $lineItem);
            $this->eventDispatcher->dispatch($event);

            $items->add($event->getPayPalLineItem());
        }

        return $items;
    }

    private function setName(OrderLineItemEntity|LineItem $lineItem, Item $item): void
    {
        $label = $lineItem->getLabel() ?? '';

        try {
            $item->setName($label);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setName(\mb_substr($label, 0, Item::MAX_LENGTH_NAME));
        }
    }

    private function setSku(OrderLineItemEntity|LineItem $lineItem, Item $item): void
    {
        $payload = $lineItem->getPayload();
        if ($payload === null || !\array_key_exists('productNumber', $payload)) {
            return;
        }

        $productNumber = $payload['productNumber'];

        try {
            $item->setSku($productNumber);
        } catch (\LengthException $e) {
            $this->logger->warning($e->getMessage(), ['lineItem' => $lineItem]);
            $item->setSku(\mb_substr($productNumber, 0, Item::MAX_LENGTH_SKU));
        }
    }

    private function buildPriceData(OrderLineItemEntity|LineItem $lineItem, Item $item, string $currencyCode, bool $isNet): void
    {
        $unitPrice = $this->priceFormatter->formatPrice($lineItem->getPrice()?->getUnitPrice() ?? 0.0, $currencyCode);

        $unitAmount = new Amount();
        $unitAmount->setCurrencyCode($currencyCode);
        $unitAmount->setValue($unitPrice);
        $item->setUnitAmount($unitAmount);
        $item->setQuantity($lineItem->getQuantity());

        $tax = new Money();
        $tax->setCurrencyCode($currencyCode);
        $tax->setValue($this->getTax($lineItem, $isNet, true, $currencyCode));
        $item->setTax($tax);
        $item->setTaxRate($this->getTaxRate($isNet, $lineItem->getPrice()));

        if (!$this->hasMismatchingPrice($lineItem, $item, $isNet, $currencyCode)) {
            return;
        }

        $unitAmount->setValue($this->priceFormatter->formatPrice($lineItem->getPrice()?->getTotalPrice() ?? 0.0, $currencyCode));
        $tax->setValue($this->getTax($lineItem, $isNet, false, $currencyCode));
        $item->setQuantity(1);
        $item->setName(\mb_substr(\sprintf('%s x %s', $lineItem->getQuantity(), $item->getName()), 0, Item::MAX_LENGTH_NAME));
    }

    private function getTax(OrderLineItemEntity|LineItem $lineItem, bool $isNet, bool $perUnit, string $currencyCode): string
    {
        $price = $lineItem->getPrice();
        if (!$isNet || $price === null) {
            return '0.00';
        }

        return $this->priceFormatter->formatPrice($price->getCalculatedTaxes()->getAmount() / ($perUnit ? $lineItem->getQuantity() : 1.0), $currencyCode);
    }

    private function getTaxRate(bool $isNet, ?CalculatedPrice $price): float
    {
        if (!$isNet || $price === null) {
            return 0.0;
        }

        $calculatedTax = $price->getCalculatedTaxes()->first();
        if ($calculatedTax === null) {
            return 0.0;
        }

        return \round($calculatedTax->getTaxRate(), 4);
    }

    private function hasMismatchingPrice(OrderLineItemEntity|LineItem $lineItem, Item $item, bool $isNet, string $currencyCode): bool
    {
        $totalTaxes = $this->getTax($lineItem, $isNet, false, $currencyCode);
        if ($totalTaxes !== $this->priceFormatter->formatPrice((float) $item->getTax()->getValue() * $lineItem->getQuantity(), $currencyCode)) {
            return true;
        }

        $totalPrice = $this->priceFormatter->formatPrice($lineItem->getPrice()?->getTotalPrice() ?? 0.0, $currencyCode);
        if ($totalPrice !== $this->priceFormatter->formatPrice((float) $item->getUnitAmount()->getValue() * $lineItem->getQuantity(), $currencyCode)) {
            return true;
        }

        return false;
    }
}
