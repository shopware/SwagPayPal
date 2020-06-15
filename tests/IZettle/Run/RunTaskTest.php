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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\AbstractTask;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\SwagPayPal;

class RunTaskTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $productSyncer;

    /**
     * @var MockObject
     */
    private $inventorySyncer;

    /**
     * @var MockObject
     */
    private $imageSyncer;

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
        $this->productSyncer = $this->createPartialMock(ProductSyncer::class, ['syncProducts']);
        $this->inventorySyncer = $this->createPartialMock(InventorySyncer::class, ['syncInventory']);
        $this->imageSyncer = $this->createPartialMock(ImageSyncer::class, ['syncImages']);
        $this->runService = $this->createMock(RunService::class);

        $this->tasks = [
            CompleteTask::class => new CompleteTask($this->runService, new NullLogger(), $this->productSyncer, $this->imageSyncer, $this->inventorySyncer),
            ProductTask::class => new ProductTask($this->runService, new NullLogger(), $this->productSyncer),
            ImageTask::class => new ImageTask($this->runService, new NullLogger(), $this->imageSyncer),
            InventoryTask::class => new InventoryTask($this->runService, new NullLogger(), $this->inventorySyncer),
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

        static::assertEquals($expectedName, $task->getRunTaskName());
    }

    public function dataProviderExecution(): array
    {
        return [
            [
                CompleteTask::class,
                [
                    'productSyncer' => 'syncProducts',
                    'imageSyncer' => 'syncImages',
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                ProductTask::class,
                [
                    'productSyncer' => 'syncProducts',
                ],
            ],
            [
                ImageTask::class,
                [
                    'imageSyncer' => 'syncImages',
                ],
            ],
            [
                InventoryTask::class,
                [
                    'inventorySyncer' => 'syncInventory',
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
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION, new IZettleSalesChannelEntity());

        $task = $this->tasks[$taskName];

        foreach ($serviceCalls as $serviceName => $serviceCall) {
            $this->$serviceName->expects(static::atLeastOnce())->method($serviceCall);
        }
        $this->runService->expects(static::once())->method('startRun');
        $this->runService->expects(static::once())->method('finishRun');

        $task->execute($salesChannel, Context::createDefaultContext());
    }
}
