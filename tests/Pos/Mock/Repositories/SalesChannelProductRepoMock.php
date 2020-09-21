<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\Bucket;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tax\TaxEntity;
use Swag\PayPal\Test\Pos\ConstantsForTesting;

class SalesChannelProductRepoMock extends AbstractRepoMock implements SalesChannelRepositoryInterface
{
    public function getDefinition(): EntityDefinition
    {
        return new ProductDefinition();
    }

    public function aggregate(Criteria $criteria, SalesChannelContext $context): AggregationResultCollection
    {
        $result = new AggregationResultCollection();

        $count = $criteria->getAggregation('count');
        if ($count !== null) {
            $result->add(new CountResult('count', $this->search($criteria, $context)->getTotal()));
        }

        $parentIds = $criteria->getAggregation('ids');
        if ($parentIds !== null) {
            $buckets = [];
            /** @var ProductEntity $product */
            foreach ($this->search($criteria, $context)->getElements() as $product) {
                $childCount = $product->getChildCount();

                if ($childCount === null || $childCount <= 0) {
                    continue;
                }

                $bucket = new Bucket($product->getId(), 0, new SumResult('count', $childCount));
                $bucket->incrementCount(1);

                $buckets[] = $bucket;
            }
            $result->add(new TermsResult('ids', $buckets));
        }

        return $result;
    }

    public function searchIds(Criteria $criteria, SalesChannelContext $context): IdSearchResult
    {
        return $this->searchCollectionIds($this->entityCollection, $criteria, $context->getContext());
    }

    public function search(Criteria $criteria, SalesChannelContext $context): EntitySearchResult
    {
        $firstFilter = \current($criteria->getFilters());

        if ($firstFilter === false) {
            return $this->searchCollection($this->entityCollection, $criteria, $context->getContext());
        }

        if ($firstFilter instanceof EqualsFilter
            && $firstFilter->getField() === 'parentId'
            && $firstFilter->getValue() === null) {
            $collection = new ProductCollection();
            /** @var ProductEntity $product */
            foreach ($this->getCollection()->getElements() as $product) {
                if ($product->getParentId() === null && !$product->getChildCount()) {
                    $collection->add($product);
                }
            }

            return $this->searchCollection($collection, $criteria, $context->getContext());
        }

        if ($firstFilter instanceof MultiFilter) {
            $subFilter = \current($firstFilter->getQueries());

            if ($firstFilter instanceof NotFilter
                && $subFilter instanceof EqualsFilter
                && $subFilter->getField() === 'parentId'
                && $subFilter->getValue() === null) {
                $collection = new ProductCollection();
                /** @var ProductEntity $product */
                foreach ($this->getCollection()->getElements() as $product) {
                    if ($product->getParentId() !== null) {
                        $collection->add($product);
                    }
                }

                return $this->searchCollection($collection, $criteria, $context->getContext());
            }

            if ($subFilter instanceof EqualsAnyFilter
                && ($subFilter->getField() === 'id' || $subFilter->getField() === 'parentId')) {
                $collection = new ProductCollection();
                /** @var ProductEntity $product */
                foreach ($this->getCollection()->getElements() as $product) {
                    if (\in_array($product->getParentId(), $subFilter->getValue(), true)
                        || \in_array($product->getId(), $subFilter->getValue(), true)) {
                        $collection->add($product);
                    }
                }

                return $this->searchCollection($collection, $criteria, $context->getContext());
            }
        }

        if ($firstFilter instanceof EqualsAnyFilter
            && $firstFilter->getField() === 'parentId') {
            $collection = new ProductCollection();
            /** @var ProductEntity $product */
            foreach ($this->getCollection()->getElements() as $product) {
                if (\in_array($product->getParentId(), $firstFilter->getValue(), true)) {
                    $collection->add($product);
                }
            }

            return $this->searchCollection($collection, $criteria, $context->getContext());
        }

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
        $entity->setChildCount(0);
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
