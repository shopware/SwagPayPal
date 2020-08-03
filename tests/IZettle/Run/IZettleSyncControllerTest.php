<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Run;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\IZettleSyncController;
use Swag\PayPal\IZettle\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\IZettle\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\IZettle\Run\Administration\LogCleaner;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\CompleteTask;
use Swag\PayPal\IZettle\Run\Task\ImageTask;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Run\Task\ProductTask;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelRepoMock;
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
    private $logCleaner;

    /**
     * @var MockObject
     */
    private $productSelection;

    /**
     * @var MockObject|RunService
     */
    private $runService;

    /**
     * @var MessageBusMock
     */
    private $messageBus;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->messageBus = new MessageBusMock();
        $this->logCleaner = $this->createMock(LogCleaner::class);
        $this->productSelection = $this->createMock(ProductSelection::class);
        $this->runService = $this->createMock(RunService::class);

        $productTask = new ProductTask($this->messageBus, $this->runService);
        $imageTask = new ImageTask($this->messageBus, $this->runService);
        $inventoryTask = new InventoryTask($this->messageBus, $this->runService);
        $completeTask = new CompleteTask($this->messageBus, $this->runService);

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
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_IMAGE,
                    SyncManagerHandler::SYNC_PRODUCT,
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                'syncInventory',
                [
                    SyncManagerHandler::SYNC_INVENTORY,
                ],
            ],
            [
                'syncProducts',
                [
                    SyncManagerHandler::SYNC_PRODUCT,
                ],
            ],
            [
                'syncImages',
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
                'cleanUpLog',
                null,
            ],
        ];
    }

    /**
     * @dataProvider dataProviderFunctions
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
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->$syncFunction($salesChannelId->getId(), $context);

        /** @var SyncManagerMessage $message */
        $message = \current($this->messageBus->getEnvelopes())->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }

    public function testCleanUpLog(): void
    {
        $context = Context::createDefaultContext();
        $this->logCleaner->expects(static::atLeastOnce())->method('cleanUpLog');
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->cleanUpLog($salesChannelId->getId(), $context);
    }

    public function testProductLog(): void
    {
        $context = Context::createDefaultContext();
        $this->productSelection->expects(static::once())->method('getProductLogCollection')->with(
            static::isInstanceOf(SalesChannelEntity::class),
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
            static::isInstanceOf(SalesChannelEntity::class),
            40,
            20
        );
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->iZettleSyncController->getProductLog($salesChannelId->getId(), new Request(['limit' => 20, 'page' => 3]), $context);
    }
}
