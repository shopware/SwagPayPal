<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\AbstractTask;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;

class RunTaskTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    private MessageBusMock $messageBus;

    private MockObject $runService;

    /**
     * @var AbstractTask[]
     */
    private array $tasks;

    public function setUp(): void
    {
        $this->messageBus = new MessageBusMock();
        $this->runService = $this->createMock(RunService::class);

        $messageDispatcher = new MessageDispatcher($this->messageBus, $this->createMock(Connection::class));
        $this->tasks = [
            CompleteTask::class => new CompleteTask($messageDispatcher, $this->runService),
            ProductTask::class => new ProductTask($messageDispatcher, $this->runService),
            ImageTask::class => new ImageTask($messageDispatcher, $this->runService),
            InventoryTask::class => new InventoryTask($messageDispatcher, $this->runService),
        ];
    }

    public function dataProviderRunTaskName(): array
    {
        return [
            [CompleteTask::class, 'complete'],
            [ProductTask::class, 'product'],
            [ImageTask::class, 'image'],
            [InventoryTask::class, 'inventory'],
        ];
    }

    /**
     * @dataProvider dataProviderRunTaskName
     *
     * @param class-string<AbstractTask> $taskName
     */
    public function testNames(string $taskName, string $expectedName): void
    {
        /** @var AbstractTask $task */
        $task = $this->getMockBuilder($taskName)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();

        static::assertSame($expectedName, $task->getRunTaskName());
    }

    public function dataProviderExecution(): array
    {
        return [
            [
                CompleteTask::class,
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_IMAGE,
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                ProductTask::class,
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                ],
            ],
            [
                ImageTask::class,
                [
                    SyncManagerHandler::SYNC_IMAGE,
                ],
            ],
            [
                InventoryTask::class,
                [
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderExecution
     *
     * @param class-string<AbstractTask> $taskName
     */
    public function testExecution(string $taskName, array $serviceCalls): void
    {
        $context = Context::createDefaultContext();

        $salesChannel = $this->getSalesChannel($context);

        $task = $this->tasks[$taskName];

        $this->runService->expects(static::once())->method('startRun');

        $task->execute($salesChannel, $context);

        $envelope = \current($this->messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        /** @var SyncManagerMessage $message */
        $message = $envelope->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }
}
