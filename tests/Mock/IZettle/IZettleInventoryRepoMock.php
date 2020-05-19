<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\IZettle;

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
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryDefinition;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryEntity;

class IZettleInventoryRepoMock implements EntityRepositoryInterface
{
    /**
     * @var IZettleSalesChannelInventoryEntity[]
     */
    private $mockEntities = [];

    public function getDefinition(): EntityDefinition
    {
        return new IZettleSalesChannelInventoryDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return new EntitySearchResult(
            \count($this->mockEntities),
            new IZettleSalesChannelInventoryCollection($this->mockEntities),
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

    public function delete(array $data, Context $context): EntityWrittenContainerEvent
    {
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

    public function addMockEntity(ProductEntity $productEntity, string $salesChannelId, int $stock): void
    {
        $entity = new IZettleSalesChannelInventoryEntity();
        $entity->setSalesChannelId($salesChannelId);
        $entity->setProductId($productEntity->getId());
        if ($productEntity->getVersionId() !== null) {
            $entity->setProductVersionId($productEntity->getVersionId());
        }
        $entity->setUniqueIdentifier(Uuid::randomHex());
        $entity->setStock($stock);
        $this->mockEntities[] = $entity;
    }
}
