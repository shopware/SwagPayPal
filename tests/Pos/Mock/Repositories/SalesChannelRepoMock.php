<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock\Repositories;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Pos\ConstantsForTesting;

/**
 * @internal
 *
 * @extends AbstractRepoMock<SalesChannelCollection>
 */
#[Package('checkout')]
class SalesChannelRepoMock extends AbstractRepoMock
{
    private SalesChannelEntity $mockEntity;

    private SalesChannelEntity $mockEntityWithNoTypeId;

    private SalesChannelEntity $mockInactiveEntity;

    public function __construct()
    {
        parent::__construct();
        $this->mockEntity = $this->createMockEntity();
        $this->mockEntityWithNoTypeId = $this->createMockEntity(true, false);
        $this->mockInactiveEntity = $this->createMockEntity(false);
    }

    public function getDefinition(): EntityDefinition
    {
        return new SalesChannelDefinition();
    }

    public function searchIds(Criteria $criteria, Context $context): IdSearchResult
    {
        return $this->searchCollectionIds($this->getFilteredCollection($criteria), $criteria, $context);
    }

    public function search(Criteria $criteria, Context $context): EntitySearchResult
    {
        return $this->searchCollection($this->getFilteredCollection($criteria), $criteria, $context);
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
        $entity->setTypeId($withTypeId ? SwagPayPal::SALES_CHANNEL_TYPE_POS : '');
        $entity->setActive($active);
        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');
        $entity->setCurrency($currency);

        $posEntity = new PosSalesChannelEntity();
        $posEntity->setApiKey(ConstantsForTesting::VALID_API_KEY);
        $entity->addExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION, $posEntity);
        $this->addMockEntity($entity);

        return $entity;
    }

    private function getFilteredCollection(Criteria $criteria): SalesChannelCollection
    {
        if ($criteria->getIds()) {
            $collection = new SalesChannelCollection();
            foreach ($criteria->getIds() as $id) {
                if (!\is_string($id)) {
                    continue;
                }
                $entity = $this->entityCollection->get($id);

                if ($entity instanceof SalesChannelEntity) {
                    $collection->add($entity);
                }
            }

            return $collection;
        }

        return $this->entityCollection;
    }
}
