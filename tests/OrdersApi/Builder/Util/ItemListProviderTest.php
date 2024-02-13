<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @internal
 */
#[Package('checkout')]
class ItemListProviderTest extends TestCase
{
    public function testNestedLineItems(): void
    {
        $order = $this->createOrder('Test Product Name', 10);

        $childLineItem = $this->createOrderLineItem('Test Child Product', 10);
        $orderLineItems = $order->getLineItems();
        static::assertNotNull($orderLineItems);
        $firstOrderLineItem = $orderLineItems->first();
        static::assertNotNull($firstOrderLineItem);
        $childLineItem->setParentId($firstOrderLineItem->getId());
        $orderLineItems->add($childLineItem);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        static::assertCount(1, $itemList);
    }

    #[DataProvider('dataProviderTaxConstellation')]
    public function testTaxes(bool $hasTaxes): void
    {
        $lineItem = $this->createOrderLineItem('test', 10.00, null, $hasTaxes);
        $lineItems = new OrderLineItemCollection([$lineItem]);

        $order = new OrderEntity();
        $order->setLineItems($lineItems);
        $order->setTaxStatus($hasTaxes ? CartPrice::TAX_STATE_NET : CartPrice::TAX_STATE_GROSS);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        $item = $itemList->first();
        static::assertInstanceOf(Item::class, $item);
        static::assertSame($hasTaxes ? 19.0 : 0.0, $item->getTaxRate());
        static::assertSame($hasTaxes ? '1.90' : '0.00', $item->getTax()->getValue());
    }

    #[DataProvider('dataProviderTaxConstellation')]
    public function testTaxesCart(bool $hasTaxes): void
    {
        $lineItem = $this->createCartLineItem('test', 10.00, null, $hasTaxes);
        $lineItems = new LineItemCollection([$lineItem]);

        $cart = new Cart(Uuid::randomHex());
        $cart->setLineItems($lineItems);
        $cart->getPrice()->assign(['taxStatus' => $hasTaxes ? CartPrice::TAX_STATE_NET : CartPrice::TAX_STATE_GROSS]);

        $itemList = $this->createItemListProvider()->getItemListFromCart($this->createCurrency(), $cart);
        $item = $itemList->first();
        static::assertInstanceOf(Item::class, $item);
        static::assertSame($hasTaxes ? 19.0 : 0.0, $item->getTaxRate());
        static::assertSame($hasTaxes ? '1.90' : '0.00', $item->getTax()->getValue());
    }

    #[DataProvider('dataProviderQuantityConstellation')]
    public function testRoundingError(float $productPrice, int $quantity, string $title, int $expectedQuantity, string $expectedUnitPrice, string $expectedTaxValue, bool $hasTaxes): void
    {
        $lineItem = $this->createOrderLineItem('test', $productPrice, null, $hasTaxes, $quantity);
        $lineItems = new OrderLineItemCollection([$lineItem]);

        $order = new OrderEntity();
        $order->setLineItems($lineItems);
        $order->setTaxStatus($hasTaxes ? CartPrice::TAX_STATE_NET : CartPrice::TAX_STATE_GROSS);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        $item = $itemList->first();
        static::assertInstanceOf(Item::class, $item);
        static::assertSame($title, $item->getName());
        static::assertSame($expectedQuantity, $item->getQuantity());
        static::assertSame($expectedUnitPrice, $item->getUnitAmount()->getValue());
        static::assertSame($hasTaxes ? 19.0 : 0.0, $item->getTaxRate());
        static::assertSame($expectedTaxValue, $item->getTax()->getValue());
    }

    #[DataProvider('dataProviderQuantityConstellation')]
    public function testRoundingErrorCart(float $productPrice, int $quantity, string $title, int $expectedQuantity, string $expectedUnitPrice, string $expectedTaxValue, bool $hasTaxes): void
    {
        $lineItem = $this->createCartLineItem('test', $productPrice, null, $hasTaxes, $quantity);
        $lineItems = new LineItemCollection([$lineItem]);

        $cart = new Cart(Uuid::randomHex());
        $cart->setLineItems($lineItems);
        $cart->getPrice()->assign(['taxStatus' => $hasTaxes ? CartPrice::TAX_STATE_NET : CartPrice::TAX_STATE_GROSS]);

        $itemList = $this->createItemListProvider()->getItemListFromCart($this->createCurrency(), $cart);
        $item = $itemList->first();
        static::assertInstanceOf(Item::class, $item);
        static::assertSame($title, $item->getName());
        static::assertSame($expectedQuantity, $item->getQuantity());
        static::assertSame($expectedUnitPrice, $item->getUnitAmount()->getValue());
        static::assertSame($hasTaxes ? 19.0 : 0.0, $item->getTaxRate());
        static::assertSame($expectedTaxValue, $item->getTax()->getValue());
    }

    public function testLineItemLabelTooLongIsTruncated(): void
    {
        $productName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam volu';
        $order = $this->createOrder($productName, 12.34);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);

        $expectedItemName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magn';
        static::assertSame($expectedItemName, $itemList->first()?->getName());
    }

    public function testLineItemLabelTooLongIsTruncatedWithPriceMismatch(): void
    {
        $lineItem = $this->createOrderLineItem(\str_repeat('a', Item::MAX_LENGTH_NAME + 10), 10, quantity: 10);

        // provoke a price mismatch
        $lineItem->getPrice()?->assign(['totalPrice' => 5]);

        $order = (new OrderEntity())->assign([
            'lineItems' => new OrderLineItemCollection([$lineItem]),
            'taxStatus' => CartPrice::TAX_STATE_GROSS,
        ]);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        static::assertCount(1, $itemList);

        $name = $itemList->getElements()[0]->getName();
        static::assertStringStartsWith('10 x ', $name);
        static::assertCount(Item::MAX_LENGTH_NAME, \mb_str_split($name));
    }

    public function testLineItemProductNumberTooLongIsTruncated(): void
    {
        $productNumber = 'SW-100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        $order = $this->createOrder('Test Product Name', 12.34, $productNumber);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        $expectedItemSku = 'SW-1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        static::assertSame($expectedItemSku, $itemList->first()?->getSku());
    }

    public static function dataProviderTaxConstellation(): iterable
    {
        return [
            'gross' => [false],
            'net' => [true],
        ];
    }

    public static function dataProviderQuantityConstellation(): iterable
    {
        return [
            [10.002, 1, 'test', 1, '10.00', '1.90', true],
            [10.002, 1, 'test', 1, '10.00', '0.00', false],
            [10.002, 2, 'test', 2, '10.00', '1.90', true],
            [10.002, 2, 'test', 2, '10.00', '0.00', false],
            [10.002, 4, '4 x test', 1, '40.01', '7.60', true],
            [10.002, 4, '4 x test', 1, '40.01', '0.00', false],
            [10.002, 55, '55 x test', 1, '550.11', '104.52', true],
            [10.002, 55, '55 x test', 1, '550.11', '0.00', false],
            [1, 10, 'test', 10, '1.00', '0.19', true],
            [1.2, 10, '10 x test', 1, '12.00', '2.28', true],
        ];
    }

    private function createItemListProvider(): ItemListProvider
    {
        return new ItemListProvider(
            new PriceFormatter(),
            $this->createMock(EventDispatcher::class),
            new NullLogger()
        );
    }

    private function createCurrency(): CurrencyEntity
    {
        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        return $currency;
    }

    private function createOrder(string $productName, float $productPrice, ?string $productNumber = null): OrderEntity
    {
        $lineItem = $this->createOrderLineItem($productName, $productPrice, $productNumber);

        $lineItems = new OrderLineItemCollection([$lineItem]);

        $order = new OrderEntity();
        $order->setLineItems($lineItems);
        $order->setTaxStatus(CartPrice::TAX_STATE_GROSS);

        return $order;
    }

    private function createOrderLineItem(
        string $productName,
        float $productPrice,
        ?string $productNumber = null,
        ?bool $withTaxes = false,
        int $quantity = 1
    ): OrderLineItemEntity {
        $lineItem = new OrderLineItemEntity();
        $lineItem->setId(Uuid::randomHex());
        $lineItem->setLabel($productName);
        $lineItem->setQuantity($quantity);

        if ($productNumber !== null) {
            $lineItem->setPayload(['productNumber' => $productNumber]);
        }

        $price = new CalculatedPrice(
            $productPrice,
            $productPrice * $quantity,
            new CalculatedTaxCollection([new CalculatedTax($withTaxes ? $productPrice * $quantity * 0.19 : 0.0, $withTaxes ? 19.0 : 0.0, $productPrice)]),
            new TaxRuleCollection()
        );
        $lineItem->setPrice($price);

        return $lineItem;
    }

    private function createCartLineItem(
        string $productName,
        float $productPrice,
        ?string $productNumber = null,
        ?bool $withTaxes = false,
        int $quantity = 1
    ): LineItem {
        $id = Uuid::randomHex();
        $lineItem = new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, $id, $quantity);
        $lineItem->setLabel($productName);

        if ($productNumber !== null) {
            $lineItem->setPayload(['productNumber' => $productNumber]);
        }

        $price = new CalculatedPrice(
            $productPrice,
            $productPrice * $quantity,
            new CalculatedTaxCollection([new CalculatedTax($withTaxes ? $productPrice * $quantity * 0.19 : 0.0, $withTaxes ? 19.0 : 0.0, $productPrice)]),
            new TaxRuleCollection()
        );
        $lineItem->setPrice($price);

        return $lineItem;
    }
}
