<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Schedule\CompleteSyncTask;
use Swag\PayPal\IZettle\Schedule\CompleteSyncTaskHandler;
use Swag\PayPal\IZettle\Schedule\InventorySyncTask;
use Swag\PayPal\IZettle\Schedule\InventorySyncTaskHandler;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelRepoMock;

class ScheduledTaskTest extends TestCase
{
    public function testCompleteSync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepositoryInterface::class);

        $productSyncer = $this->createPartialMock(ProductSyncer::class, ['syncProducts']);
        $inventorySyncer = $this->createPartialMock(InventorySyncer::class, ['syncInventory']);
        $imageSyncer = $this->createPartialMock(ImageSyncer::class, ['syncImages']);
        $runService = $this->createMock(RunService::class);
        $completeTask = new CompleteTask($runService, new NullLogger(), $productSyncer, $imageSyncer, $inventorySyncer);

        $taskHandler = new CompleteSyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $completeTask);

        $runService->expects(static::once())->method('startRun');
        $productSyncer->expects(static::exactly(2))->method('syncProducts');
        $imageSyncer->expects(static::once())->method('syncImages');
        $inventorySyncer->expects(static::once())->method('syncInventory');
        $runService->expects(static::once())->method('finishRun');

        static::assertContains(CompleteSyncTask::class, CompleteSyncTaskHandler::getHandledMessages());
        $taskHandler->run();
    }

    public function testInventorySync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepositoryInterface::class);

        $inventorySyncer = $this->createPartialMock(InventorySyncer::class, ['syncInventory']);
        $runService = $this->createMock(RunService::class);
        $inventoryTask = new InventoryTask($runService, new NullLogger(), $inventorySyncer);

        $taskHandler = new InventorySyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $inventoryTask);

        $runService->expects(static::once())->method('startRun');
        $inventorySyncer->expects(static::once())->method('syncInventory');
        $runService->expects(static::once())->method('finishRun');

        static::assertContains(InventorySyncTask::class, InventorySyncTaskHandler::getHandledMessages());
        $taskHandler->run();
    }
}
