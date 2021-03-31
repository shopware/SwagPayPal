<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Repositories;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;

class OrderRepositoryMock implements EntityRepositoryInterface
{
    use PaymentTransactionTrait;

    public const NO_ORDER = 'searchResultWithoutOrder';
    public const NO_ORDER_TRANSACTIONS = 'searchResultWithoutOrderTransactions';
    public const NO_ORDER_TRANSACTION = 'searchResultWithoutOrderTransaction';

    public function getDefinition(): EntityDefinition
    {
        return new OrderDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($context->hasExtension(self::NO_ORDER)) {
            $orderCollection = new OrderCollection([]);
        } elseif ($context->hasExtension(self::NO_ORDER_TRANSACTIONS)) {
            $orderEntity = $this->getOrderEntity();
            $orderEntity->assign(['transactions' => null]);
            $orderCollection = new OrderCollection([$orderEntity]);
        } elseif ($context->hasExtension(self::NO_ORDER_TRANSACTION)) {
            $orderEntity = $this->getOrderEntity();
            $orderEntity->setTransactions(new OrderTransactionCollection());
            $orderCollection = new OrderCollection([$orderEntity]);
        } else {
            $orderCollection = new OrderCollection([$this->getOrderEntity()]);
        }

        return new EntitySearchResult(
            $this->getDefinition()->getEntityName(),
            \count($orderCollection),
            $orderCollection,
            null,
            $criteria,
            $context
        );
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

    public function delete(array $ids, Context $context): EntityWrittenContainerEvent
    {
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null, ?CloneBehavior $behavior = null): EntityWrittenContainerEvent
    {
    }

    private function getOrderEntity(): OrderEntity
    {
        $orderEntity = $this->createOrderEntity(ConstantsForTesting::VALID_ORDER_ID);

        $orderTransaction = $this->createOrderTransaction();
        $orderTransaction->setOrderId($orderEntity->getId());

        $orderEntity->setTransactions(new OrderTransactionCollection([$orderTransaction]));

        return $orderEntity;
    }
}
