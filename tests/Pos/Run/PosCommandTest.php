<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Command\AbstractPosCommand;
use Swag\PayPal\Pos\Command\PosImageSyncCommand;
use Swag\PayPal\Pos\Command\PosInventorySyncCommand;
use Swag\PayPal\Pos\Command\PosLogCleanupCommand;
use Swag\PayPal\Pos\Command\PosProductSyncCommand;
use Swag\PayPal\Pos\Command\PosSyncCommand;
use Swag\PayPal\Pos\Command\PosSyncResetCommand;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Administration\SyncResetter;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Symfony\Component\Clock\NativeClock;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @internal
 */
#[Package('checkout')]
class PosCommandTest extends TestCase
{
    private const INVALID_CHANNEL_ID = 'notASalesChannelId';

    private SalesChannelRepoMock $salesChannelRepoMock;

    /**
     * @var AbstractPosCommand[]
     */
    private array $commands;

    private MessageBusMock $messageBus;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->messageBus = new MessageBusMock();
        $runService = static::createStub(RunService::class);

        $messageDispatcher = new MessageDispatcher($this->messageBus, static::createStub(Connection::class), new NativeClock());
        $productTask = new ProductTask($messageDispatcher, $runService);
        $imageTask = new ImageTask($messageDispatcher, $runService);
        $inventoryTask = new InventoryTask($messageDispatcher, $runService);
        $completeTask = new CompleteTask($messageDispatcher, $runService);

        $this->commands = [
            PosSyncCommand::class => new PosSyncCommand($this->salesChannelRepoMock, $completeTask),
            PosImageSyncCommand::class => new PosImageSyncCommand($this->salesChannelRepoMock, $imageTask),
            PosInventorySyncCommand::class => new PosInventorySyncCommand($this->salesChannelRepoMock, $inventoryTask),
            PosProductSyncCommand::class => new PosProductSyncCommand($this->salesChannelRepoMock, $productTask),
            PosLogCleanupCommand::class => new PosLogCleanupCommand($this->salesChannelRepoMock, static::createStub(LogCleaner::class)),
            PosSyncResetCommand::class => new PosSyncResetCommand($this->salesChannelRepoMock, static::createStub(SyncResetter::class)),
        ];
    }

    public static function dataProviderSyncFunctions(): array
    {
        return [
            [
                PosSyncCommand::class,
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_IMAGE,
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                PosInventorySyncCommand::class,
                [
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                PosProductSyncCommand::class,
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                ],
            ],
            [
                PosImageSyncCommand::class,
                [
                    SyncManagerHandler::SYNC_IMAGE,
                ],
            ],
        ];
    }

    /**
     * @return \Generator<int, array{string}>
     */
    public static function dataProviderFunctions(): \Generator
    {
        foreach (static::dataProviderSyncFunctions() as [$function]) {
            yield [$function];
        }

        yield [PosLogCleanupCommand::class];
        yield [PosSyncResetCommand::class];
    }

    #[DataProvider('dataProviderFunctions')]
    public function testSyncWithInvalidId(string $commandClassName): void
    {
        $input = new ArrayInput(['salesChannelId' => self::INVALID_CHANNEL_ID]);
        static::assertSame(1, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }

    #[DataProvider('dataProviderFunctions')]
    public function testSyncWithValidId(string $commandClassName): void
    {
        $input = new ArrayInput(['salesChannelId' => $this->salesChannelRepoMock->getMockEntity()->getId()]);
        static::assertSame(0, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }

    #[DataProvider('dataProviderSyncFunctions')]
    public function testSyncNormal(string $commandClassName, array $serviceCalls): void
    {
        $input = new ArrayInput([]);

        static::assertSame(0, $this->commands[$commandClassName]->run($input, new NullOutput()));

        $envelope = \current($this->messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        /** @var SyncManagerMessage $message */
        $message = $envelope->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }

    public function testLogCleanup(): void
    {
        $logCleaner = $this->createMock(LogCleaner::class);
        $logCleaner->expects($this->exactly($this->salesChannelRepoMock->getCollection()->count()))->method('cleanUpLog');
        $command = new PosLogCleanupCommand($this->salesChannelRepoMock, $logCleaner);

        static::assertSame(0, $command->run(new ArrayInput([]), new NullOutput()));
    }

    public function testSyncReset(): void
    {
        $syncResetter = $this->createMock(SyncResetter::class);
        $syncResetter->expects($this->exactly($this->salesChannelRepoMock->getCollection()->count()))->method('resetSync');
        $command = new PosSyncResetCommand($this->salesChannelRepoMock, $syncResetter);

        static::assertSame(0, $command->run(new ArrayInput([]), new NullOutput()));
    }
}
