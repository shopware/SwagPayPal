<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Read\ReadCriteria;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregatorResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use SwagPayPal\Test\Helper\ConstantsForTesting;

class OrderRepoMock implements RepositoryInterface
{
    public const EXPECTED_ITEM_NAME = 'Aerodynamic Paper Ginger Vitro';

    public const EXPECTED_ITEM_CURRENCY = 'EUR';

    public const EXPECTED_ITEM_PRICE = '540.19';

    public const EXPECTED_ITEM_QUANTITY = 1;

    public const EXPECTED_ITEM_SKU = '0716562764cd43389abe16faad1838b8';

    public const EXPECTED_ITEM_TAX = '37.81';

    public function aggregate(Criteria $criteria, Context $context): AggregatorResult
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
    }

    public function read(ReadCriteria $criteria, Context $context): EntityCollection
    {
        $collection = new OrderCollection();
        $order = new OrderEntity();

        switch ($criteria->getIds()[0]) {
            case ConstantsForTesting::VALID_ORDER_ID:
                $order->setId(ConstantsForTesting::VALID_ORDER_ID);
                $order->setLineItems($this->getLineItems(true));
                break;
            case ConstantsForTesting::ORDER_ID_MISSING_PRICE:
                $order->setId(ConstantsForTesting::ORDER_ID_MISSING_PRICE);
                $order->setLineItems($this->getLineItems());
                break;
            default:
                $order->setId(ConstantsForTesting::ORDER_ID_MISSING_LINE_ITEMS);
        }

        $collection->add($order);

        return $collection;
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    private function getLineItems(bool $setPrice = false): OrderLineItemCollection
    {
        $orderLineItem = new OrderLineItemEntity();

        $orderLineItem->setId('6198ff79c4144931919977829dbca3d6');
        $orderLineItem->setQuantity(1);

        if ($setPrice) {
            $orderLineItem->setPrice(
                new CalculatedPrice(
                    578.0,
                    578.0,
                    new CalculatedTaxCollection([
                        new CalculatedTax(37.81, 7, 578),
                    ]),
                    new TaxRuleCollection([
                        7 => new TaxRule(7),
                    ])
                )
            );
        }

        $orderLineItem->setLabel(self::EXPECTED_ITEM_NAME);
        $orderLineItem->setPayload([
            'id' => self::EXPECTED_ITEM_SKU,
        ]);

        return new OrderLineItemCollection([
            $orderLineItem,
        ]);
    }
}
