<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run\Administration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Run\Administration\SyncResetter;

/**
 * @internal
 */
#[Package('checkout')]
class SyncResetterTest extends TestCase
{
    public function testResetSync(): void
    {
        $salesChannelId = Uuid::randomHex();
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);
        $entityId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        $productRepo = $this->createMock(EntityRepository::class);
        $productRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::equalTo($criteria), $context)
            ->willReturn(new IdSearchResult(1, [['primaryKey' => ['salesChannelId' => $salesChannelId, 'productId' => $entityId], 'data' => []]], $criteria, $context));
        $productRepo
            ->expects(static::once())
            ->method('delete')
            ->with([['salesChannelId' => $salesChannelId, 'productId' => $entityId]], $context);

        $inventoryRepo = $this->createMock(EntityRepository::class);
        $inventoryRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::equalTo($criteria), $context)
            ->willReturn(new IdSearchResult(1, [['primaryKey' => ['salesChannelId' => $salesChannelId, 'productId' => $entityId], 'data' => []]], $criteria, $context));
        $inventoryRepo
            ->expects(static::once())
            ->method('delete')
            ->with([['salesChannelId' => $salesChannelId, 'productId' => $entityId]], $context);

        $mediaRepo = $this->createMock(EntityRepository::class);
        $mediaRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::equalTo($criteria), $context)
            ->willReturn(new IdSearchResult(1, [['primaryKey' => ['salesChannelId' => $salesChannelId, 'mediaId' => $entityId], 'data' => []]], $criteria, $context));
        $mediaRepo
            ->expects(static::once())
            ->method('delete')
            ->with([['salesChannelId' => $salesChannelId, 'mediaId' => $entityId]], $context);

        $runRepo = $this->createMock(EntityRepository::class);
        $runRepo
            ->expects(static::once())
            ->method('searchIds')
            ->with(static::equalTo($criteria), $context)
            ->willReturn(new IdSearchResult(1, [['primaryKey' => $entityId, 'data' => []]], $criteria, $context));
        $runRepo
            ->expects(static::once())
            ->method('delete')
            ->with([['id' => $entityId]], $context);

        $resetter = new SyncResetter(
            $productRepo,
            $inventoryRepo,
            $mediaRepo,
            $runRepo,
        );

        $resetter->resetSync($salesChannel, $context);
    }
}
