<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Schedule\CompleteSyncTask;
use Swag\PayPal\Pos\Schedule\CompleteSyncTaskHandler;
use Swag\PayPal\Pos\Schedule\InventorySyncTask;
use Swag\PayPal\Pos\Schedule\InventorySyncTaskHandler;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;

class ScheduledTaskTest extends TestCase
{
    public function testCompleteSync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepositoryInterface::class);

        $messageBus = new MessageBusMock();
        $runRepository = new RunRepoMock();
        $runService = new RunService($runRepository, new RunLogRepoMock(), new Logger('test'));
        $completeTask = new CompleteTask($messageBus, $runService);

        $taskHandler = new CompleteSyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $completeTask);

        static::assertEmpty($runRepository->getCollection());
        static::assertContains(CompleteSyncTask::class, CompleteSyncTaskHandler::getHandledMessages());

        $taskHandler->run();

        static::assertCount($salesChannelRepoMock->getCollection()->count(), $runRepository->getCollection());

        /** @var SyncManagerMessage $message */
        $message = \current($messageBus->getEnvelopes())->getMessage();
        static::assertSame($completeTask->getSteps(), $message->getSteps());
    }

    public function testInventorySync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepositoryInterface::class);

        $messageBus = new MessageBusMock();
        $runRepository = new RunRepoMock();
        $runService = new RunService($runRepository, new RunLogRepoMock(), new Logger('test'));
        $inventoryTask = new InventoryTask($messageBus, $runService);

        $taskHandler = new InventorySyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $inventoryTask);

        static::assertEmpty($runRepository->getCollection());
        static::assertContains(InventorySyncTask::class, InventorySyncTaskHandler::getHandledMessages());

        $taskHandler->run();

        static::assertCount($salesChannelRepoMock->getCollection()->count(), $runRepository->getCollection());

        /** @var SyncManagerMessage $message */
        $message = \current($messageBus->getEnvelopes())->getMessage();
        static::assertSame($inventoryTask->getSteps(), $message->getSteps());
    }
}
