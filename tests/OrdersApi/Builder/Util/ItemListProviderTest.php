<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
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

        return $order;
    }

    private function createLineItem(
        string $productName,
        float $productPrice,
        ?string $productNumber = null
    ): OrderLineItemEntity {
        $lineItem = new OrderLineItemEntity();
        $lineItem->setId(Uuid::randomHex());
        $lineItem->setLabel($productName);
        $lineItem->setQuantity(1);
        $lineItem->setUnitPrice($productPrice);

        if ($productNumber !== null) {
            $lineItem->setPayload(['productNumber' => $productNumber]);
        }

        return $lineItem;
    }
}
