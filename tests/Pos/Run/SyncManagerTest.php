<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductSingleSyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;

/**
 * @internal
 */
#[Package('checkout')]
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

    protected function setUp(): void
    {
        $this->messageBus = new MessageBusMock();
        $this->context = Context::createDefaultContext();
        $this->runService = $this->getContainer()->get(RunService::class);

        $messageDispatcher = new MessageDispatcher($this->messageBus, $this->getContainer()->get(Connection::class));
        $this->task = new CompleteTask($messageDispatcher, $this->runService);

        $this->context = Context::createDefaultContext();
        $this->salesChannel = $this->getSalesChannel($this->context);

        $imageSyncer = $this->createMock(ImageSyncManager::class);
        $imageSyncer
            ->method('createMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING))
            ->willReturn([]);
        $inventorySyncer = $this->createMock(InventorySyncManager::class);
        $inventorySyncer
            ->method('createMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING))
            ->willReturn([]);
        $productSyncer = $this->createMock(ProductSyncManager::class);
        $productSyncer
            ->method('createMessages')
            ->with($this->salesChannel, $this->context, static::isType(IsType::TYPE_STRING))
            ->willReturn([new ProductSingleSyncMessage()], []);
        $salesChannelRepo = new SalesChannelRepoMock();
        $salesChannelRepo->addMockEntity($this->salesChannel);
        $messageHydrator = new MessageHydrator($this->createMock(SalesChannelContextService::class), $salesChannelRepo);

        $this->syncManagerHandler = new SyncManagerHandler(
            $messageDispatcher,
            $messageHydrator,
            $this->runService,
            new NullLogger(),
            $imageSyncer,
            $inventorySyncer,
            $productSyncer
        );
    }

    public function testProcessMessages(): void
    {
        $runId = $this->task->execute($this->salesChannel, $this->context);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 0, $runId);

        $this->messageBus->execute([$this->syncManagerHandler], false);
        static::assertSame(1, $this->messageBus->getTotalWaitingMessages());
        $this->assertRemainingMessage(ProductSingleSyncMessage::class);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 1, $runId);
    }

    public function testAbortsOnRemainingMessages(): void
    {
        $runId = $this->task->execute($this->salesChannel, $this->context);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 0, $runId);

        $this->messageBus->execute([$this->syncManagerHandler], false);
        static::assertSame(1, $this->messageBus->getTotalWaitingMessages());
        $this->assertRemainingMessage(ProductSingleSyncMessage::class);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 1, $runId);

        $this->messageBus->clear();
        $this->messageBus->execute([$this->syncManagerHandler]);
        $this->assertRemainingMessage(null);
        $this->assertRunStatus(PosSalesChannelRunDefinition::STATUS_IN_PROGRESS, 1, $runId);
    }

    /**
     * @param class-string|null $class
     */
    private function assertRemainingMessage(?string $class): void
    {
        $envelope = \current($this->messageBus->getEnvelopes());
        if (!$class) {
            static::assertFalse($envelope);

            return;
        }

        static::assertNotFalse($envelope);
        $message = $envelope->getMessage();
        static::assertInstanceOf($class, $message);
    }

    private function assertRunStatus(string $status, int $count, string $runId): void
    {
        /** @var EntityRepository $runRepository */
        $runRepository = $this->getContainer()->get('swag_paypal_pos_sales_channel_run.repository');

        /** @var PosSalesChannelRunEntity|null $run */
        $run = $runRepository->search(new Criteria([$runId]), $this->context)->first();
        static::assertNotNull($run);

        static::assertSame($count, $run->getMessageCount());
        static::assertSame($status, $run->getStatus());
    }
}
