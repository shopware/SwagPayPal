<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\MessageQueue\MessageQueueStatsDefinition;
use Shopware\Core\Framework\MessageQueue\MessageQueueStatsEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class MessageQueueStatsRepoMock extends AbstractRepoMock implements EntityRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new MessageQueueStatsDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
        $result = new AggregationResultCollection();

        $count = $criteria->getAggregation('totalSize');
        if ($count !== null) {
            $firstFilter = \current($criteria->getFilters());

            $messageClasses = $firstFilter instanceof EqualsAnyFilter ? $firstFilter->getValue() : null;

            $result->add(new SumResult('totalSize', $this->getTotalWaitingMessages($messageClasses)));
        }

        return $result;
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        return $this->searchCollectionIds($this->entityCollection, $criteria, $context);
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return $this->searchCollection($this->entityCollection, $criteria, $context);
    }

    public function update(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->updateCollection($data, $context);
    }

    public function upsert(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->updateCollection($data, $context);
    }

    public function create(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->updateCollection($data, $context);
    }

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
        return $this->removeFromCollection($data, $context);
    }

    public function createVersion(string $id, Context $context, ?string $name = null, ?string $versionId = null): string
    {
    }

    public function merge(string $versionId, Context $context): void
    {
    }

    public function clone(string $id, Context $context, ?string $newId = null): EntityWrittenContainerEvent
    {
    }

    public function modifyMessageStat(string $name, int $size): void
    {
        foreach ($this->entityCollection as $item) {
            /** @var MessageQueueStatsEntity $item */
            if ($item->getName() === $name) {
                $item->setSize($item->getSize() + $size);

                return;
            }
        }

        $newEntity = new MessageQueueStatsEntity();
        $newEntity->setId(Uuid::randomHex());
        $newEntity->setName($name);
        $newEntity->setSize($size);

        $this->addMockEntity($newEntity);
    }

    public function getTotalWaitingMessages(?array $messageClasses = null): int
    {
        $count = 0;
        foreach ($this->entityCollection as $item) {
            /** @var MessageQueueStatsEntity $item */
            if ($messageClasses === null || \in_array($item->getName(), $messageClasses, true)) {
                $count += $item->getSize();
            }
        }

        return $count;
    }
}
