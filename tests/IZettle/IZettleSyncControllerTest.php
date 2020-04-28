<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Swag\PayPal\IZettle\Exception\UnexpectedSalesChannelTypeException;
use Swag\PayPal\IZettle\IZettleSyncController;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelRepoMock;

class IZettleSyncControllerTest extends TestCase
{
    private const INVALID_CHANNEL_ID = 'notASalesChannelId';

    /**
     * @var IZettleSyncController
     */
    private $iZettleSyncController;

    /**
     * @var SalesChannelRepoMock
     */
    private $salesChannelRepoMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProductSyncer
     */
    private $productSyncerMock;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->productSyncerMock = $this->createPartialMock(ProductSyncer::class, ['syncProducts']);
        $this->iZettleSyncController = new IZettleSyncController(
            $this->productSyncerMock,
            $this->salesChannelRepoMock
        );
    }

    public function testProductSyncWithInvalidId(): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(InvalidSalesChannelIdException::class);
        $this->iZettleSyncController->syncProducts(self::INVALID_CHANNEL_ID, $context);
    }

    public function testProductSyncWithInvalidTypeId(): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(UnexpectedSalesChannelTypeException::class);
        $salesChannelId = $this->salesChannelRepoMock->getMockEntityWithNoTypeId();
        $this->iZettleSyncController->syncProducts($salesChannelId->getId(), $context);
    }

    public function testProductSyncWithInactiveSalesChannel(): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(InvalidSalesChannelIdException::class);
        $salesChannelId = $this->salesChannelRepoMock->getMockInactiveEntity();
        $this->iZettleSyncController->syncProducts($salesChannelId->getId(), $context);
    }

    public function testProductSync(): void
    {
        $context = Context::createDefaultContext();
        $this->productSyncerMock->expects(static::once())->method('syncProducts');
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->syncProducts($salesChannelId->getId(), $context);
    }
}
