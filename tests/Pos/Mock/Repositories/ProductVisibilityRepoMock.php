<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityCollection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductVisibilityRepoMock extends AbstractRepoMock
{
    public function getDefinition(): EntityDefinition
    {
        return new ProductVisibilityDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
        return new AggregationResultCollection([
            new CountResult('count', $this->getCollection()->count()),
        ]);
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        return $this->searchCollectionIds($this->getFilteredCollection($criteria), $criteria, $context);
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return $this->searchCollection($this->getFilteredCollection($criteria), $criteria, $context);
    }

    public function createMockEntity(string $salesChannelId, int $visibility, ?ProductEntity $productEntity = null): ProductVisibilityEntity
    {
        if ($productEntity === null) {
            $productEntity = new ProductEntity();
            $productEntity->setId(Uuid::randomHex());
            $productEntity->setVersionId(Uuid::randomHex());
            $productEntity->setUniqueIdentifier(Uuid::randomHex());
        }

        $entity = new ProductVisibilityEntity();
        $entity->setSalesChannelId($salesChannelId);
        $entity->setProductId($productEntity->getId());
        $entity->setVisibility($visibility);
        $entity->setProduct($productEntity);
        $entity->setId(Uuid::randomHex());
        $this->entityCollection->add($entity);

        return $entity;
    }

    public function filterBySalesChannelId(string $id): ProductVisibilityCollection
    {
        /** @var ProductVisibilityCollection $entityCollection */
        $entityCollection = $this->entityCollection;

        return $entityCollection->filter(function (ProductVisibilityEntity $productVisibility) use ($id) {
            return $productVisibility->getSalesChannelId() === $id;
        });
    }

    private function getFilteredCollection(Criteria $criteria): ProductVisibilityCollection
    {
        $salesChannelId = null;
        foreach ($criteria->getFilters() as $filter) {
            if ($filter instanceof EqualsFilter && $filter->getField() === 'salesChannelId') {
                $salesChannelId = (string) $filter->getValue();
            }
        }

        if ($salesChannelId !== null) {
            return $this->filterBySalesChannelId($salesChannelId);
        }

        /** @var ProductVisibilityCollection $entityCollection */
        $entityCollection = $this->entityCollection;

        return $entityCollection;
    }
}
