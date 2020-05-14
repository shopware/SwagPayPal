<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\IZettle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\SwagPayPal;

class SalesChannelRepoMock implements EntityRepositoryInterface
{
    /**
     * @var SalesChannelEntity
     */
    private $mockEntity;

    /**
     * @var SalesChannelEntity
     */
    private $mockEntityWithNoTypeId;

    /**
     * @var SalesChannelEntity
     */
    private $mockInactiveEntity;

    public function __construct()
    {
        $this->mockEntity = $this->createMockEntity();
        $this->mockEntityWithNoTypeId = $this->createMockEntity(true, false);
        $this->mockInactiveEntity = $this->createMockEntity(false);
    }

    public function getDefinition(): EntityDefinition
    {
        return new SalesChannelDefinition();
    }

    public function aggregate(Criteria $criteria, Context $context): AggregationResultCollection
    {
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        return new IdSearchResult(1, [], $criteria, $context);
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        if ($criteria->getIds()) {
            if (in_array($this->mockEntityWithNoTypeId->getId(), $criteria->getIds(), true)) {
                return new EntitySearchResult(
                    1,
                    new SalesChannelCollection([$this->mockEntityWithNoTypeId]),
                    null,
                    $criteria,
                    $context
                );
            }

            if (in_array($this->mockInactiveEntity->getId(), $criteria->getIds(), true)) {
                return new EntitySearchResult(
                    1,
                    new SalesChannelCollection([$this->mockInactiveEntity]),
                    null,
                    $criteria,
                    $context
                );
            }

            if (!in_array($this->mockEntity->getId(), $criteria->getIds(), true)) {
                return new EntitySearchResult(
                    0,
                    new SalesChannelCollection([]),
                    null,
                    $criteria,
                    $context
                );
            }
        }

        return new EntitySearchResult(
            1,
            new SalesChannelCollection([$this->mockEntity]),
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

    public function getMockEntity(): SalesChannelEntity
    {
        return $this->mockEntity;
    }

    public function getMockEntityWithNoTypeId(): SalesChannelEntity
    {
        return $this->mockEntityWithNoTypeId;
    }

    public function getMockInactiveEntity(): SalesChannelEntity
    {
        return $this->mockInactiveEntity;
    }

    private function createMockEntity(bool $active = true, bool $withTypeId = true): SalesChannelEntity
    {
        $id = Uuid::randomHex();
        $entity = new SalesChannelEntity();
        $entity->setId($id);
        $entity->setTypeId($withTypeId ? SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE : '');
        $entity->setActive($active);
        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');
        $entity->setCurrency($currency);

        $iZettleEntity = new IZettleSalesChannelEntity();
        $entity->addExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION, $iZettleEntity);

        return $entity;
    }
}
