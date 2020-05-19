<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\IZettle;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductRepoMock implements SalesChannelRepositoryInterface
{
    /**
     * @var ProductEntity[]
     */
    private $mockEntities = [];

    public function getDefinition(): EntityDefinition
    {
        return new ProductDefinition();
    }

    public function aggregate(Criteria $criteria, SalesChannelContext $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, SalesChannelContext $context): IdSearchResult
    {
    }

    public function search(Criteria $criteria, SalesChannelContext $context): EntitySearchResult
    {
        return new EntitySearchResult(
            \count($this->mockEntities),
            new ProductCollection($this->mockEntities),
            null,
            $criteria,
            $context->getContext()
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

    public function addMockEntity(ProductEntity $productEntity): void
    {
        $this->mockEntities[] = $productEntity;
    }
}
