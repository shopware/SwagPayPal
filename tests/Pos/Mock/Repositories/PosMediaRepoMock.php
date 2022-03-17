<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;

class PosMediaRepoMock extends AbstractRepoMock
{
    public function getDefinition(): EntityDefinition
    {
        return new PosSalesChannelMediaDefinition();
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

    public function createMockEntity(MediaEntity $mediaEntity, string $salesChannelId, ?string $lookupKey = null, ?string $url = null): PosSalesChannelMediaEntity
    {
        $entity = new PosSalesChannelMediaEntity();
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

    /**
     * @return string[]
     */
    protected function getPrimaryKeyWrite(Entity $entity): array
    {
        return [
            'salesChannelId' => $entity->get('salesChannelId'),
            'mediaId' => $entity->get('mediaId'),
        ];
    }

    /**
     * @return string[]
     */
    protected function getPrimaryKeyRead(Entity $entity): array
    {
        return [
            'sales_channel_id' => $entity->get('salesChannelId'),
            'media_id' => $entity->get('mediaId'),
        ];
    }

    private function getFilteredCollection(Criteria $criteria): PosSalesChannelMediaCollection
    {
        foreach ($criteria->getFilters() as $filter) {
            if ($filter instanceof EqualsFilter && $filter->getField() === 'lookupKey') {
                /** @var PosSalesChannelMediaCollection $newCollection */
                $newCollection = $this->entityCollection->filterByProperty('lookupKey', null);

                return $newCollection;
            }
        }

        /** @var PosSalesChannelMediaCollection $entityCollection */
        $entityCollection = $this->entityCollection;

        return $entityCollection;
    }
}
