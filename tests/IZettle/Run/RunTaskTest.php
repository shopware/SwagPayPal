<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\IZettle\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\IZettle\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\AbstractTask;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\Test\IZettle\Helper\SalesChannelTrait;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;

class RunTaskTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    /**
     * @var MessageBusMock
     */
    private $messageBus;

    /**
     * @var MockObject
     */
    private $runService;

    /**
     * @var AbstractTask[]
     */
    private $tasks;

    public function setUp(): void
    {
        $this->messageBus = new MessageBusMock();
        $this->runService = $this->createMock(RunService::class);

        $this->tasks = [
            CompleteTask::class => new CompleteTask($this->messageBus, $this->runService),
            ProductTask::class => new ProductTask($this->messageBus, $this->runService),
            ImageTask::class => new ImageTask($this->messageBus, $this->runService),
            InventoryTask::class => new InventoryTask($this->messageBus, $this->runService),
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
            ->setMethodsExcept(['getRunTaskName'])
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

        /** @var SyncManagerMessage $message */
        $message = \current($this->messageBus->getEnvelopes())->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }
}
