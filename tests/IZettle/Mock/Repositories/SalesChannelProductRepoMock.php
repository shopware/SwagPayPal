<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Mock\Repositories;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;

class SalesChannelProductRepoMock extends AbstractRepoMock implements SalesChannelRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new ProductDefinition();
    }

    public function aggregate(Criteria $criteria, SalesChannelContext $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, SalesChannelContext $context): IdSearchResult
    {
        return $this->searchCollectionIds($this->entityCollection, $criteria, $context->getContext());
    }

    public function search(Criteria $criteria, SalesChannelContext $context): EntitySearchResult
    {
        return $this->searchCollection($this->entityCollection, $criteria, $context->getContext());
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

    public function createMockEntity(
        TaxEntity $tax,
        CategoryEntity $category,
        string $name,
        string $id,
        ?string $parentId = null,
        ?MediaEntity $mediaEntity = null
    ): SalesChannelProductEntity {
        $entity = new SalesChannelProductEntity();
        $entity->setId($id);
        $entity->setVersionId(Uuid::randomHex());
        $entity->setParentId($parentId);
        $entity->setName($name);
        $entity->setDescription(ConstantsForTesting::PRODUCT_DESCRIPTION);
        $entity->setProductNumber(ConstantsForTesting::PRODUCT_NUMBER);
        $shopwarePrice = new CalculatedPrice(ConstantsForTesting::PRODUCT_PRICE, ConstantsForTesting::PRODUCT_PRICE, new CalculatedTaxCollection(), new TaxRuleCollection());
        $entity->setCalculatedPrice($shopwarePrice);
        $entity->setPurchasePrice(ConstantsForTesting::PRODUCT_PRICE * 2);
        if ($mediaEntity) {
            $media = new ProductMediaEntity();
            $media->setMedia($mediaEntity);
            $entity->setCover($media);
        }

        $entity->addTranslated('name', $name);
        $entity->addTranslated('description', ConstantsForTesting::PRODUCT_DESCRIPTION);
        $entity->setCategories(new CategoryCollection([$category]));
        $entity->setTax($tax);
        $this->addMockEntity($entity);

        return $entity;
    }
}
