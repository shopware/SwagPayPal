<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductEntity;

class PosProductRepoMock extends AbstractRepoMock implements EntityRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new PosSalesChannelProductDefinition();
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

    public function delete(array $ids, Context $context): EntityWrittenContainerEvent
    {
        return $this->removeFromCollection($ids, $context);
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

    public function createMockEntity(ProductEntity $productEntity, Product $product, string $salesChannelId): PosSalesChannelProductEntity
    {
        $entity = new PosSalesChannelProductEntity();
        $entity->setSalesChannelId($salesChannelId);
        $entity->setProductId($productEntity->getId());
        $versionId = $productEntity->getVersionId();
        if ($versionId !== null) {
            $entity->setProductVersionId($versionId);
        }
        $entity->setChecksum($product->generateChecksum());
        $entity->setUniqueIdentifier($this->getUniqueIdentifier($entity));
        $this->addMockEntity($entity);

        return $entity;
    }

    protected function getUniqueIdentifier(Entity $entity): string
    {
        return \implode('-', [
            $entity->get('salesChannelId'),
            $entity->get('productId'),
        ]);
    }

    /**
     * @return string[]
     */
    protected function getPrimaryKeyWrite(Entity $entity): array
    {
        return [
            'salesChannelId' => $entity->get('salesChannelId'),
            'productId' => $entity->get('productId'),
        ];
    }

    /**
     * @return string[]
     */
    protected function getPrimaryKeyRead(Entity $entity): array
    {
        return [
            'sales_channel_id' => $entity->get('salesChannelId'),
            'product_id' => $entity->get('productId'),
        ];
    }
}
