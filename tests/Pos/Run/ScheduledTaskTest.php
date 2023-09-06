<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunLogCollection;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Schedule\CleanUpLogTask;
use Swag\PayPal\Pos\Schedule\CleanUpLogTaskHandler;
use Swag\PayPal\Pos\Schedule\CompleteSyncTask;
use Swag\PayPal\Pos\Schedule\CompleteSyncTaskHandler;
use Swag\PayPal\Pos\Schedule\InventorySyncTask;
use Swag\PayPal\Pos\Schedule\InventorySyncTaskHandler;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
class ScheduledTaskTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testCompleteSync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);

        $messageBus = new MessageBusMock();
        $runRepository = new RunRepoMock();
        $runService = new RunServiceMock($runRepository, new RunLogRepoMock(), $this->createMock(Connection::class), new Logger('test'));
        $completeTask = new CompleteTask(new MessageDispatcher($messageBus, $this->createMock(Connection::class)), $runService);

        $taskHandler = new CompleteSyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $completeTask);

        static::assertEmpty($runRepository->getCollection());
        static::assertContains(CompleteSyncTask::class, CompleteSyncTaskHandler::getHandledMessages());

        $taskHandler->run();

        static::assertCount($salesChannelRepoMock->getCollection()->count(), $runRepository->getCollection());

        $envelope = \current($messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        /** @var SyncManagerMessage $message */
        $message = $envelope->getMessage();
        static::assertSame($completeTask->getSteps(), $message->getSteps());
    }

    public function testInventorySync(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);

        $messageBus = new MessageBusMock();
        $runRepository = new RunRepoMock();
        $runService = new RunServiceMock($runRepository, new RunLogRepoMock(), $this->createMock(Connection::class), new Logger('test'));
        $inventoryTask = new InventoryTask(new MessageDispatcher($messageBus, $this->createMock(Connection::class)), $runService);

        $taskHandler = new InventorySyncTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $inventoryTask);

        static::assertEmpty($runRepository->getCollection());
        static::assertContains(InventorySyncTask::class, InventorySyncTaskHandler::getHandledMessages());

        $taskHandler->run();

        static::assertCount($salesChannelRepoMock->getCollection()->count(), $runRepository->getCollection());

        $envelope = \current($messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        /** @var SyncManagerMessage $message */
        $message = $envelope->getMessage();
        static::assertSame($inventoryTask->getSteps(), $message->getSteps());
    }

    public function testCleanUpLog(): void
    {
        $salesChannelRepoMock = new SalesChannelRepoMock();
        $scheduledTaskRepository = $this->createMock(EntityRepository::class);

        $runRepository = new RunRepoMock();
        $runA = new PosSalesChannelRunEntity();
        $runA->setId(Uuid::randomHex());
        $runA->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $runA->setLogs(new PosSalesChannelRunLogCollection());
        $runB = new PosSalesChannelRunEntity();
        $runB->setId(Uuid::randomHex());
        $runB->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $runB->setLogs(new PosSalesChannelRunLogCollection());
        $runRepository->addMockEntity($runA);
        $runRepository->addMockEntity($runB);
        $logCleaner = new LogCleaner($runRepository);

        $taskHandler = new CleanUpLogTaskHandler($scheduledTaskRepository, $salesChannelRepoMock, $logCleaner);

        static::assertCount(2, $runRepository->getCollection());
        static::assertContains(CleanUpLogTask::class, CleanUpLogTaskHandler::getHandledMessages());

        $taskHandler->run();

        static::assertCount(1, $runRepository->getCollection());
    }
}
