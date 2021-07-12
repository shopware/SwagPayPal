<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;

class SyncManagerTest extends TestCase
{
    use IntegrationTestBehaviour;
    use SalesChannelTrait;

    private Context $context;

    private SalesChannelEntity $salesChannel;

    private CompleteTask $task;

    private MessageBusMock $messageBus;

    private SyncManagerHandler $syncManagerHandler;

    private RunService $runService;

    public function setUp(): void
    {
        $this->messageBus = new MessageBusMock();
        $this->context = Context::createDefaultContext();

        /** @var RunService $runService */
        $runService = $this->getContainer()->get(RunService::class);
        $this->runService = $runService;

        $this->task = new CompleteTask($this->messageBus, $runService);

        $this->context = Context::createDefaultContext();
        $this->salesChannel = $this->getSalesChannel($this->context);

        $imageSyncer = $this->createMock(ImageSyncManager::class);
        $imageSyncer
            ->method('createMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING))
            ->willReturn(0);
        $inventorySyncer = $this->createMock(InventorySyncManager::class);
        $inventorySyncer
            ->method('createMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING))
            ->willReturn(0);
        $productSyncer = $this->createMock(ProductSyncManager::class);
        $productSyncer
            ->method('createMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING))
            ->willReturn(1, 0);

        $this->syncManagerHandler = new SyncManagerHandler(
            $this->messageBus,
            $this->runService,
            new NullLogger(),
            $imageSyncer,
            $inventorySyncer,
            $productSyncer
        );
    }

    public function testWaitOnce(): void
    {
        $runId = $this->task->execute($this->salesChannel, $this->context);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 0, $runId);

        $this->runService->setMessageCount(1, $runId, $this->context);
        $this->assertMessageStep(0);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 1, $runId);

        $this->messageBus->execute([$this->syncManagerHandler], false);

        $this->assertMessageStep(0);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 1, $runId);
        $this->runService->decrementMessageCount($runId);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 0, $runId);

        $this->messageBus->execute([$this->syncManagerHandler], false);
        static::assertSame(1, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());
        $this->assertMessageStep(1);
        $this->runService->decrementMessageCount($runId);

        $this->messageBus->execute([$this->syncManagerHandler]);
        static::assertSame(0, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_FINISHED, 0, $runId);
    }

    public function testWaitForeverAndAbort(): void
    {
        $runId = $this->task->execute($this->salesChannel, $this->context);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 0, $runId);

        $this->runService->setMessageCount(1, $runId, $this->context);
        $this->assertMessageStep(0);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 1, $runId);

        $this->messageBus->execute([$this->syncManagerHandler]);
        static::assertSame(0, $this->messageBus->getMessageQueueStatsRepository()->getTotalWaitingMessages());
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_FAILED, 0, $runId);
    }

    private function assertMessageStep(int $int): void
    {
        $envelope = \current($this->messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        $message = $envelope->getMessage();
        static::assertInstanceOf(SyncManagerMessage::class, $message);
        static::assertSame($int, $message->getCurrentStep());
    }

    private function assertRunStatus(string $status, int $count, string $runId): void
    {
        /** @var EntityRepositoryInterface $runRepository */
        $runRepository = $this->getContainer()->get('swag_paypal_pos_sales_channel_run.repository');

        /** @var PosSalesChannelRunEntity|null $run */
        $run = $runRepository->search(new Criteria([$runId]), $this->context)->first();
        static::assertNotNull($run);

        static::assertSame($count, $run->getMessageCount());
        static::assertSame($status, $run->getStatus());
    }
}
