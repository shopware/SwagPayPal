<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Run;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\PosSyncController;
use Swag\PayPal\Pos\Run\Administration\LogCleaner;
use Swag\PayPal\Pos\Run\Administration\SyncResetter;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\CompleteTask;
use Swag\PayPal\Pos\Run\Task\ImageTask;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\Pos\Run\Task\ProductTask;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class PosSyncControllerTest extends TestCase
{
    private const INVALID_CHANNEL_ID = 'notASalesChannelId';

    private PosSyncController $posSyncController;

    private SalesChannelRepoMock $salesChannelRepoMock;

    private MockObject $logCleaner;

    private MockObject $productSelection;

    private MockObject $runService;

    private MessageBusMock $messageBus;

    private MockObject $syncResetter;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->messageBus = new MessageBusMock();
        $this->logCleaner = $this->createMock(LogCleaner::class);
        $this->productSelection = $this->createMock(ProductSelection::class);
        $this->runService = $this->createMock(RunService::class);
        $this->syncResetter = $this->createMock(SyncResetter::class);

        $messageDispatcher = new MessageDispatcher($this->messageBus, $this->createMock(Connection::class));
        $productTask = new ProductTask($messageDispatcher, $this->runService);
        $imageTask = new ImageTask($messageDispatcher, $this->runService);
        $inventoryTask = new InventoryTask($messageDispatcher, $this->runService);
        $completeTask = new CompleteTask($messageDispatcher, $this->runService);

        $this->posSyncController = new PosSyncController(
            $this->salesChannelRepoMock,
            $completeTask,
            $productTask,
            $imageTask,
            $inventoryTask,
            $this->logCleaner,
            $this->runService,
            $this->syncResetter,
            $this->productSelection
        );
    }

    public static function dataProviderSyncFunctions(): array
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

    public static function dataProviderFunctions(): array
    {
        return static::dataProviderSyncFunctions() + [
            [
                'cleanUpLog',
                null,
            ],
            [
                'resetSync',
                null,
            ],
        ];
    }

    #[DataProvider('dataProviderFunctions')]
    public function testSyncWithInvalidId(string $syncFunction): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(InvalidSalesChannelIdException::class);
        $this->posSyncController->$syncFunction(self::INVALID_CHANNEL_ID, $context);
    }

    #[DataProvider('dataProviderSyncFunctions')]
    public function testSyncNormal(string $syncFunction, array $serviceCalls): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->posSyncController->$syncFunction($salesChannelId->getId(), $context);

        $envelope = \current($this->messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        /** @var SyncManagerMessage $message */
        $message = $envelope->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }

    public function testAbortSync(): void
    {
        $this->runService->expects(static::once())->method('abortRun');
        $this->posSyncController->abortSync(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testCleanUpLog(): void
    {
        $context = Context::createDefaultContext();
        $this->logCleaner->expects(static::atLeastOnce())->method('clearLog');
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->posSyncController->cleanUpLog($salesChannelId->getId(), $context);
    }

    public function testResetSync(): void
    {
        $context = Context::createDefaultContext();
        $this->syncResetter->expects(static::atLeastOnce())->method('resetSync');
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();
        $this->posSyncController->resetSync($salesChannelId->getId(), $context);
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
        $this->posSyncController->getProductLog($salesChannelId->getId(), new Request(), $context);
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
        $this->posSyncController->getProductLog($salesChannelId->getId(), new Request(['limit' => 20, 'page' => 3]), $context);
    }
}
