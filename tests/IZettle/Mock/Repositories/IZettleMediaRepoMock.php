<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Repositories;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaDefinition;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaEntity;

class IZettleMediaRepoMock extends AbstractRepoMock implements EntityRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new IZettleSalesChannelMediaDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
        $result = new AggregationResultCollection();

        $count = $criteria->getAggregation('count');
        if ($count !== null) {
            $result->add(new CountResult('count', $this->search($criteria, $context)->getTotal()));
        }

        return $result;
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        return $this->searchCollectionIds($this->getFilteredCollection($criteria), $criteria, $context);
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return $this->searchCollection($this->getFilteredCollection($criteria), $criteria, $context);
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

    public function createMockEntity(MediaEntity $mediaEntity, string $salesChannelId, ?string $lookupKey = null, ?string $url = null): IZettleSalesChannelMediaEntity
    {
        $entity = new IZettleSalesChannelMediaEntity();
        $entity->setSalesChannelId($salesChannelId);
        $entity->setMediaId($mediaEntity->getId());
        $entity->setMedia($mediaEntity);
        $entity->setUniqueIdentifier($this->getUniqueIdentifier($entity));
        $entity->setLookupKey($lookupKey);
        $entity->setUrl($url);
        $this->addMockEntity($entity);

        return $entity;
    }

    protected function getUniqueIdentifier(Entity $entity): string
    {
        return \implode('-', [
            $entity->get('salesChannelId'),
            $entity->get('mediaId'),
        ]);
    }

    private function getFilteredCollection(Criteria $criteria): IZettleSalesChannelMediaCollection
    {
        foreach ($criteria->getFilters() as $filter) {
            if ($filter instanceof MultiFilter && $filter->getOperator() === MultiFilter::CONNECTION_AND) {
                /** @var IZettleSalesChannelMediaCollection $newCollection */
                $newCollection = $this->entityCollection->filterByProperty('lookupKey', null);

                return $newCollection;
            }
        }

        /** @var IZettleSalesChannelMediaCollection $entityCollection */
        $entityCollection = $this->entityCollection;

        return $entityCollection;
    }
}
