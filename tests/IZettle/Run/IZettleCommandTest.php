<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Swag\PayPal\IZettle\Command\AbstractIZettleCommand;
use Swag\PayPal\IZettle\Command\IZettleImageSyncCommand;
use Swag\PayPal\IZettle\Command\IZettleInventorySyncCommand;
use Swag\PayPal\IZettle\Command\IZettleLogCleanupCommand;
use Swag\PayPal\IZettle\Command\IZettleProductSyncCommand;
use Swag\PayPal\IZettle\Command\IZettleSyncCommand;
use Swag\PayPal\IZettle\Run\Administration\LogCleaner;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
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
     * @var MockObject|ProductSyncer
     */
    private $productSyncer;

    /**
     * @var MockObject|InventorySyncer
     */
    private $inventorySyncer;

    /**
     * @var MockObject|RunService
     */
    private $runService;

    /**
     * @var MockObject|LogCleaner
     */
    private $logCleaner;

    /**
     * @var MockObject|ImageSyncer
     */
    private $imageSyncer;

    /**
     * @var AbstractIZettleCommand[]
     */
    private $commands;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->productSyncer = $this->createPartialMock(ProductSyncer::class, ['syncProducts']);
        $this->inventorySyncer = $this->createPartialMock(InventorySyncer::class, ['syncInventory']);
        $this->imageSyncer = $this->createPartialMock(ImageSyncer::class, ['syncImages']);
        $this->runService = $this->createMock(RunService::class);

        $this->logCleaner = $this->createMock(LogCleaner::class);
        $productTask = new ProductTask($this->runService, new NullLogger(), $this->productSyncer);
        $imageTask = new ImageTask($this->runService, new NullLogger(), $this->imageSyncer);
        $inventoryTask = new InventoryTask($this->runService, new NullLogger(), $this->inventorySyncer);
        $completeTask = new CompleteTask($this->runService, new NullLogger(), $this->productSyncer, $this->imageSyncer, $this->inventorySyncer);

        $this->commands = [
            IZettleSyncCommand::class => new IZettleSyncCommand($this->salesChannelRepoMock, $completeTask),
            IZettleImageSyncCommand::class => new IZettleImageSyncCommand($this->salesChannelRepoMock, $imageTask),
            IZettleInventorySyncCommand::class => new IZettleInventorySyncCommand($this->salesChannelRepoMock, $inventoryTask),
            IZettleProductSyncCommand::class => new IZettleProductSyncCommand($this->salesChannelRepoMock, $productTask),
            IZettleLogCleanupCommand::class => new IZettleLogCleanupCommand($this->salesChannelRepoMock, $this->logCleaner),
        ];
    }

    public function dataProviderSyncFunctions(): array
    {
        return [
            [
                IZettleSyncCommand::class,
                [
                    'productSyncer' => 'syncProducts',
                    'imageSyncer' => 'syncImages',
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                IZettleInventorySyncCommand::class,
                [
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                IZettleProductSyncCommand::class,
                [
                    'productSyncer' => 'syncProducts',
                ],
            ],
            [
                IZettleImageSyncCommand::class,
                [
                    'imageSyncer' => 'syncImages',
                ],
            ],
            [
                IZettleLogCleanupCommand::class,
                [
                    'logCleaner' => 'cleanUpLog',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncWithInvalidId(string $commandClassName): void
    {
        $input = new ArrayInput(['salesChannelId' => self::INVALID_CHANNEL_ID]);
        static::assertEquals(1, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncWithValidId(string $commandClassName): void
    {
        $input = new ArrayInput(['salesChannelId' => $this->salesChannelRepoMock->getMockEntity()->getId()]);
        static::assertEquals(0, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncNormal(string $commandClassName, array $serviceCalls): void
    {
        foreach ($serviceCalls as $serviceName => $serviceCall) {
            $this->$serviceName->expects(static::atLeastOnce())->method($serviceCall);
        }
        $input = new ArrayInput([]);

        static::assertEquals(0, $this->commands[$commandClassName]->run($input, new NullOutput()));
    }
}
