<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Repositories;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductRepoMock extends AbstractRepoMock implements EntityRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new ProductDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
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

    public function createMockEntity(string $name, int $stock, int $availableStock, ?string $id = null): ProductEntity
    {
        $entity = new ProductEntity();
        $entity->setId($id ?? Uuid::randomHex());
        $entity->setVersionId(Uuid::randomHex());
        $entity->setUniqueIdentifier($this->getUniqueIdentifier($entity));
        $entity->setName($name);
        $entity->setStock($stock);
        $entity->setAvailableStock($availableStock);
        $this->entityCollection->add($entity);

        return $entity;
    }
}
