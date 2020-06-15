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
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\IZettleSyncController;
use Swag\PayPal\IZettle\Run\Administration\LogCleaner;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\IZettle\Sync\ProductSyncer;
use Swag\PayPal\Test\Mock\IZettle\SalesChannelRepoMock;
use Symfony\Component\HttpFoundation\Request;

class IZettleSyncControllerTest extends TestCase
{
    private const INVALID_CHANNEL_ID = 'notASalesChannelId';

    /**
     * @var IZettleSyncController
     */
    private $iZettleSyncController;

    /**
     * @var SalesChannelRepoMock
     */
    private $salesChannelRepoMock;

    /**
     * @var MockObject
     */
    private $productSyncer;

    /**
     * @var MockObject
     */
    private $imageSyncer;

    /**
     * @var MockObject
     */
    private $inventorySyncer;

    /**
     * @var MockObject
     */
    private $logCleaner;

    /**
     * @var MockObject
     */
    private $productSelection;

    /**
     * @var MockObject|RunService
     */
    private $runService;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->productSyncer = $this->createMock(ProductSyncer::class);
        $this->imageSyncer = $this->createMock(ImageSyncer::class);
        $this->inventorySyncer = $this->createMock(InventorySyncer::class);
        $this->logCleaner = $this->createMock(LogCleaner::class);
        $this->productSelection = $this->createMock(ProductSelection::class);
        $this->runService = $this->createMock(RunService::class);

        $productTask = new ProductTask($this->runService, new NullLogger(), $this->productSyncer);
        $imageTask = new ImageTask($this->runService, new NullLogger(), $this->imageSyncer);
        $inventoryTask = new InventoryTask($this->runService, new NullLogger(), $this->inventorySyncer);
        $completeTask = new CompleteTask($this->runService, new NullLogger(), $this->productSyncer, $this->imageSyncer, $this->inventorySyncer);

        $this->iZettleSyncController = new IZettleSyncController(
            $this->salesChannelRepoMock,
            $completeTask,
            $productTask,
            $imageTask,
            $inventoryTask,
            $this->logCleaner,
            $this->productSelection
        );
    }

    public function dataProviderSyncFunctions(): array
    {
        return [
            [
                'syncAll',
                [
                    'productSyncer' => 'syncProducts',
                    'imageSyncer' => 'syncImages',
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                'syncInventory',
                [
                    'inventorySyncer' => 'syncInventory',
                ],
            ],
            [
                'syncProducts',
                [
                    'productSyncer' => 'syncProducts',
                ],
            ],
            [
                'syncImages',
                [
                    'imageSyncer' => 'syncImages',
                ],
            ],
            [
                'cleanUpLog',
                [
                    'logCleaner' => 'cleanUpLog',
                ],
            ],
        ];
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncWithInvalidId(string $syncFunction): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(InvalidSalesChannelIdException::class);
        $this->iZettleSyncController->$syncFunction(self::INVALID_CHANNEL_ID, $context);
    }

    /**
     * @dataProvider dataProviderSyncFunctions
     */
    public function testSyncNormal(string $syncFunction, array $serviceCalls): void
    {
        $context = Context::createDefaultContext();
        foreach ($serviceCalls as $serviceName => $serviceCall) {
            $this->$serviceName->expects(static::atLeastOnce())->method($serviceCall);
        }
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->$syncFunction($salesChannelId->getId(), $context);
    }

    public function testProductLog(): void
    {
        $context = Context::createDefaultContext();
        $this->productSelection->expects(static::once())->method('getProductLogCollection')->with(
            static::isInstanceOf(IZettleSalesChannelEntity::class),
            0,
            10
        );
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->getProductLog($salesChannelId->getId(), new Request(), $context);
    }

    public function testProductLogPaginated(): void
    {
        $context = Context::createDefaultContext();
        $this->productSelection->expects(static::once())->method('getProductLogCollection')->with(
            static::isInstanceOf(IZettleSalesChannelEntity::class),
            40,
            20
        );
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->getProductLog($salesChannelId->getId(), new Request(['limit' => 20, 'page' => 3]), $context);
    }
}
