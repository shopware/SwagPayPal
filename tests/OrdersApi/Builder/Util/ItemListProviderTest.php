<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Item;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Util\PriceFormatter;

class ItemListProviderTest extends TestCase
{
    public function testNestedLineItems(): void
    {
        $order = $this->createOrder('Test Product Name', 10);

        $childLineItem = $this->createLineItem('Test Child Product', 10);
        $orderLineItems = $order->getLineItems();
        static::assertNotNull($orderLineItems);
        $firstOrderLineItem = $orderLineItems->first();
        static::assertNotNull($firstOrderLineItem);
        $childLineItem->setParentId($firstOrderLineItem->getId());
        $orderLineItems->add($childLineItem);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        static::assertCount(1, $itemList);
    }

    /**
     * @dataProvider dataProviderTaxConstellation
     */
    public function testTaxes(bool $hasTaxes): void
    {
        $lineItem = $this->createLineItem('test', 10, null, $hasTaxes);
        $lineItems = new OrderLineItemCollection([$lineItem]);

        $order = new OrderEntity();
        $order->setLineItems($lineItems);
        $order->setTaxStatus($hasTaxes ? CartPrice::TAX_STATE_NET : CartPrice::TAX_STATE_GROSS);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        $item = \current($itemList);
        static::assertInstanceOf(Item::class, $item);
        static::assertSame($hasTaxes ? 19.0 : 0.0, $item->getTaxRate());
        static::assertSame($hasTaxes ? '1.90' : '0.00', $item->getTax()->getValue());
    }

    public function testLineItemLabelTooLongIsTruncated(): void
    {
        $productName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam volu';
        $order = $this->createOrder($productName, 12.34);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);

        $expectedItemName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliqu';
        static::assertSame($expectedItemName, $itemList[0]->getName());
    }

    public function testLineItemProductNumberTooLongIsTruncated(): void
    {
        $productNumber = 'SW-100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        $order = $this->createOrder('Test Product Name', 12.34, $productNumber);

        $itemList = $this->createItemListProvider()->getItemList($this->createCurrency(), $order);
        $expectedItemSku = 'SW-1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        static::assertSame($expectedItemSku, $itemList[0]->getSku());
    }

    public function dataProviderTaxConstellation(): iterable
    {
        return [
            'gross' => [false],
            'net' => [true],
        ];
    }

    private function createItemListProvider(): ItemListProvider
    {
        return new ItemListProvider(
            new PriceFormatter(),
            new EventDispatcherMock(),
            new LoggerMock()
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
        $lineItem = $this->createLineItem($productName, $productPrice, $productNumber);

        $lineItems = new OrderLineItemCollection([$lineItem]);

        $order = new OrderEntity();
        $order->setLineItems($lineItems);
        $order->setTaxStatus(CartPrice::TAX_STATE_GROSS);

        return $order;
    }

    private function createLineItem(
        string $productName,
        float $productPrice,
        ?string $productNumber = null,
        ?bool $withTaxes = false
    ): OrderLineItemEntity {
        $lineItem = new OrderLineItemEntity();
        $lineItem->setId(Uuid::randomHex());
        $lineItem->setLabel($productName);
        $lineItem->setQuantity(1);
        $lineItem->setUnitPrice($productPrice);

        if ($productNumber !== null) {
            $lineItem->setPayload(['productNumber' => $productNumber]);
        }

        $price = new CalculatedPrice(
            $productPrice,
            $productPrice,
            new CalculatedTaxCollection([new CalculatedTax($withTaxes ? $productPrice * 0.19 : 0.0, $withTaxes ? 19.0 : 0.0, $productPrice)]),
            new TaxRuleCollection()
        );
        $lineItem->setPrice($price);

        return $lineItem;
    }
}
