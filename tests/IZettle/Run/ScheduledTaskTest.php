<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Schedule\CompleteSyncTask;
use Swag\PayPal\IZettle\Schedule\CompleteSyncTaskHandler;
use Swag\PayPal\IZettle\Schedule\InventorySyncTask;
use Swag\PayPal\IZettle\Schedule\InventorySyncTaskHandler;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelRepoMock;

class ScheduledTaskTest extends TestCase
{
    public function testCompleteSync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepositoryInterface::class);

        $messageBus = new MessageBusMock();
        $runService = $this->createMock(RunService::class);
        $completeTask = new CompleteTask($messageBus, $runService);

        $taskHandler = new CompleteSyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $completeTask);

        $runService->expects(static::once())->method('startRun');

        static::assertContains(CompleteSyncTask::class, CompleteSyncTaskHandler::getHandledMessages());
        $taskHandler->run();

        /** @var SyncManagerMessage $message */
        $message = \current($messageBus->getEnvelopes())->getMessage();
        static::assertSame($completeTask->getSteps(), $message->getSteps());
    }

    public function testInventorySync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepositoryInterface::class);

        $messageBus = new MessageBusMock();
        $runService = $this->createMock(RunService::class);
        $inventoryTask = new InventoryTask($messageBus, $runService);

        $taskHandler = new InventorySyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $inventoryTask);

        $runService->expects(static::once())->method('startRun');

        static::assertContains(InventorySyncTask::class, InventorySyncTaskHandler::getHandledMessages());
        $taskHandler->run();

        /** @var SyncManagerMessage $message */
        $message = \current($messageBus->getEnvelopes())->getMessage();
        static::assertSame($inventoryTask->getSteps(), $message->getSteps());
    }
}
