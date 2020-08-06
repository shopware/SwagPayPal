<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync;

use Monolog\Logger;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\IZettle\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\IZettle\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\IZettle\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\IZettle\MessageQueue\Message\CloneVisibilityMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ImageSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductSingleSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductVariantSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Schedule\InventorySyncTask;
use Swag\PayPal\Test\IZettle\Helper\SalesChannelTrait;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\RunRepoMock;

class SyncManagerTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var SalesChannelEntity
     */
    private $salesChannel;

    /**
     * @var CompleteTask
     */
    private $task;

    /**
     * @var MessageBusMock
     */
    private $messageBus;

    /**
     * @var SyncManagerHandler
     */
    private $syncManagerHandler;

    public function setUp(): void
    {
        $this->messageBus = new MessageBusMock();

        $runService = new RunService(
            new RunRepoMock(),
            new RunLogRepoMock(),
            new Logger('test')
        );

        $this->task = new CompleteTask($this->messageBus, $runService);

        $this->context = Context::createDefaultContext();
        $this->salesChannel = $this->getSalesChannel($this->context);

        $imageSyncer = $this->createMock(ImageSyncManager::class);
        $imageSyncer
            ->expects(static::once())
            ->method('buildMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING));
        $inventorySyncer = $this->createMock(InventorySyncManager::class);
        $inventorySyncer
            ->expects(static::once())
            ->method('buildMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING));
        $productSyncer = $this->createMock(ProductSyncManager::class);
        $productSyncer
            ->expects(static::exactly(2))
            ->method('buildMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING));

        $this->syncManagerHandler = new SyncManagerHandler(
            $this->messageBus,
            $this->messageBus->getMessageQueueStatsRepository(),
            $runService,
            new NullLogger(),
            $imageSyncer,
            $inventorySyncer,
            $productSyncer
        );
    }

    public function testStart(): void
    {
        $this->task->execute($this->salesChannel, $this->context);

        $this->messageBus->execute([$this->syncManagerHandler]);
    }

    public function dataProviderWaitingMessageClasses(): array
    {
        return [
            [CloneVisibilityMessage::class, true],
            [ImageSyncMessage::class, true],
            [InventorySyncMessage::class, true],
            [ProductSingleSyncMessage::class, true],
            [ProductVariantSyncMessage::class, true],
            [ProductCleanupSyncMessage::class, true],
            [InventorySyncTask::class, false],
        ];
    }

    /**
     * @dataProvider dataProviderWaitingMessageClasses
     */
    public function testWait(string $waitingClass, bool $waits): void
    {
        static::assertSame(0, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());

        $this->task->execute($this->salesChannel, $this->context);
        $this->messageBus->getMessageQueueStatsRepository()->modifyMessageStat($waitingClass, 1);

        static::assertSame(2, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());
        $this->assertMessageStep(0);

        $this->messageBus->execute([$this->syncManagerHandler], false);

        $this->assertMessageStep($waits ? 0 : 1);
        $this->messageBus->getMessageQueueStatsRepository()->modifyMessageStat($waitingClass, -1);
        static::assertSame(1, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());

        $this->messageBus->execute([$this->syncManagerHandler], false);
        $this->assertMessageStep($waits ? 1 : 2);

        $this->messageBus->execute([$this->syncManagerHandler]);

        static::assertSame(0, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());
    }

    private function assertMessageStep(int $int): void
    {
        $message = \current($this->messageBus->getEnvelopes())->getMessage();
        static::assertInstanceOf(SyncManagerMessage::class, $message);
        static::assertSame($int, $message->getCurrentStep());
    }
}
