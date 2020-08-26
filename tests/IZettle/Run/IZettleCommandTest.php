<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Swag\PayPal\IZettle\Command\AbstractIZettleCommand;
use Swag\PayPal\IZettle\Command\IZettleImageSyncCommand;
use Swag\PayPal\IZettle\Command\IZettleInventorySyncCommand;
use Swag\PayPal\IZettle\Command\IZettleLogCleanupCommand;
use Swag\PayPal\IZettle\Command\IZettleProductSyncCommand;
use Swag\PayPal\IZettle\Command\IZettleSyncCommand;
use Swag\PayPal\IZettle\Command\IZettleSyncResetCommand;
use Swag\PayPal\IZettle\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\IZettle\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\IZettle\Run\Administration\LogCleaner;
use Swag\PayPal\IZettle\Run\Administration\SyncResetter;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelRepoMock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class IZettleCommandTest extends TestCase
{
    private const INVALID_CHANNEL_ID = 'notASalesChannelId';

    /**
     * @var SalesChannelRepoMock
     */
    private $salesChannelRepoMock;

    /**
     * @var MockObject|RunService
     */
    private $runService;

    /**
     * @var MockObject
     */
    private $logCleaner;

    /**
     * @var MockObject
     */
    private $syncResetter;

    /**
     * @var AbstractIZettleCommand[]
     */
    private $commands;

    /**
     * @var MessageBusMock
     */
    private $messageBus;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->messageBus = new MessageBusMock();
        $this->runService = $this->createMock(RunService::class);
        $this->logCleaner = $this->createMock(LogCleaner::class);
        $this->syncResetter = $this->createMock(SyncResetter::class);

        $productTask = new ProductTask($this->messageBus, $this->runService);
        $imageTask = new ImageTask($this->messageBus, $this->runService);
        $inventoryTask = new InventoryTask($this->messageBus, $this->runService);
        $completeTask = new CompleteTask($this->messageBus, $this->runService);

        $this->commands = [
            IZettleSyncCommand::class => new IZettleSyncCommand($this->salesChannelRepoMock, $completeTask),
            IZettleImageSyncCommand::class => new IZettleImageSyncCommand($this->salesChannelRepoMock, $imageTask),
            IZettleInventorySyncCommand::class => new IZettleInventorySyncCommand($this->salesChannelRepoMock, $inventoryTask),
            IZettleProductSyncCommand::class => new IZettleProductSyncCommand($this->salesChannelRepoMock, $productTask),
            IZettleLogCleanupCommand::class => new IZettleLogCleanupCommand($this->salesChannelRepoMock, $this->logCleaner),
            IZettleSyncResetCommand::class => new IZettleSyncResetCommand($this->salesChannelRepoMock, $this->syncResetter),
        ];
    }

    public function dataProviderSyncFunctions(): array
    {
        return [
            [
                IZettleSyncCommand::class,
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_IMAGE,
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                IZettleInventorySyncCommand::class,
                [
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                IZettleProductSyncCommand::class,
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                ],
            ],
            [
                IZettleImageSyncCommand::class,
                [
                    SyncManagerHandler::SYNC_IMAGE,
                ],
            ],
        ];
    }

    public function dataProviderFunctions(): array
    {
        return $this->dataProviderSyncFunctions() + [
            [
                IZettleLogCleanupCommand::class,
                null,
            ],
            [
                IZettleSyncResetCommand::class,
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderFunctions
     */
    public function testSyncWithInvalidId(string $commandClassName): void
    {
        $input = new ArrayInput(['salesChannelId' => self::INVALID_CHANNEL_ID]);
        static::assertSame(1, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }

    /**
     * @dataProvider dataProviderFunctions
     */
    public function testSyncWithValidId(string $commandClassName): void
    {
        $input = new ArrayInput(['salesChannelId' => $this->salesChannelRepoMock->getMockEntity()->getId()]);
        static::assertSame(0, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncNormal(string $commandClassName, array $serviceCalls): void
    {
        $input = new ArrayInput([]);

        static::assertSame(0, $this->commands[$commandClassName]->run($input, new NullOutput()));

        /** @var SyncManagerMessage $message */
        $message = \current($this->messageBus->getEnvelopes())->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }

    public function testLogCleanup(): void
    {
        $this->logCleaner->expects(static::exactly($this->salesChannelRepoMock->getCollection()->count()))->method('cleanUpLog');
        $input = new ArrayInput([]);

        static::assertSame(0, $this->commands[IZettleLogCleanupCommand::class]->run($input, new NullOutput()));
    }

    public function testSyncReset(): void
    {
        $this->syncResetter->expects(static::exactly($this->salesChannelRepoMock->getCollection()->count()))->method('resetSync');
        $input = new ArrayInput([]);

        static::assertSame(0, $this->commands[IZettleSyncResetCommand::class]->run($input, new NullOutput()));
    }
}
