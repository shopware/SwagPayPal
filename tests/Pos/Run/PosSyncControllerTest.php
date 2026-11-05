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
use Symfony\Component\Clock\NativeClock;
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

    private MessageBusMock $messageBus;

    protected function setUp(): void
    {
        $this->salesChannelRepoMock = new SalesChannelRepoMock();
        $this->messageBus = new MessageBusMock();
        $this->posSyncController = $this->createController();
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

    /**
     * @return \Generator<int, array{string}>
     */
    public static function dataProviderFunctions(): \Generator
    {
        foreach (static::dataProviderSyncFunctions() as [$function]) {
            yield [$function];
        }

        yield ['cleanUpLog'];
        yield ['resetSync'];
    }

    #[DataProvider('dataProviderFunctions')]
    public function testSyncWithInvalidId(string $syncFunction): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(InvalidSalesChannelIdException::class);

        // @phpstan-ignore method.dynamicName
        $this->posSyncController->{$syncFunction}(self::INVALID_CHANNEL_ID, $context);
    }

    #[DataProvider('dataProviderSyncFunctions')]
    public function testSyncNormal(string $syncFunction, array $serviceCalls): void
    {
        $context = Context::createDefaultContext();
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();

        // @phpstan-ignore method.dynamicName
        $this->posSyncController->{$syncFunction}($salesChannelId->getId(), $context);

        $envelope = \current($this->messageBus->getEnvelopes());
        static::assertNotFalse($envelope);
        /** @var SyncManagerMessage $message */
        $message = $envelope->getMessage();
        static::assertSame($serviceCalls, $message->getSteps());
    }

    public function testAbortSync(): void
    {
        $runService = $this->createMock(RunService::class);
        $runService->expects($this->once())->method('abortRun');

        $this->createController(runService: $runService)->abortSync(Uuid::randomHex(), Context::createDefaultContext());
    }

    public function testCleanUpLog(): void
    {
        $logCleaner = $this->createMock(LogCleaner::class);
        $logCleaner->expects($this->atLeastOnce())->method('clearLog');
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();

        $this->createController(logCleaner: $logCleaner)->cleanUpLog($salesChannelId->getId(), Context::createDefaultContext());
    }

    public function testResetSync(): void
    {
        $syncResetter = $this->createMock(SyncResetter::class);
        $syncResetter->expects($this->atLeastOnce())->method('resetSync');
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();

        $this->createController(syncResetter: $syncResetter)->resetSync($salesChannelId->getId(), Context::createDefaultContext());
    }

    public function testProductLog(): void
    {
        $productSelection = $this->createMock(ProductSelection::class);
        $productSelection->expects($this->once())->method('getProductLogCollection')->with(
            static::isInstanceOf(SalesChannelEntity::class),
            0,
            10
        );
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();

        $this->createController(productSelection: $productSelection)->getProductLog($salesChannelId->getId(), new Request(), Context::createDefaultContext());
    }

    public function testProductLogPaginated(): void
    {
        $productSelection = $this->createMock(ProductSelection::class);
        $productSelection->expects($this->once())->method('getProductLogCollection')->with(
            static::isInstanceOf(SalesChannelEntity::class),
            40,
            20
        );
        $salesChannelId = $this->salesChannelRepoMock->getMockEntity();

        $this->createController(productSelection: $productSelection)->getProductLog($salesChannelId->getId(), new Request(['limit' => 20, 'page' => 3]), Context::createDefaultContext());
    }

    private function createController(
        ?LogCleaner $logCleaner = null,
        ?ProductSelection $productSelection = null,
        ?RunService $runService = null,
        ?SyncResetter $syncResetter = null,
    ): PosSyncController {
        $runService ??= static::createStub(RunService::class);
        $messageDispatcher = new MessageDispatcher($this->messageBus, static::createStub(Connection::class), new NativeClock());

        return new PosSyncController(
            $this->salesChannelRepoMock,
            new CompleteTask($messageDispatcher, $runService),
            new ProductTask($messageDispatcher, $runService),
            new ImageTask($messageDispatcher, $runService),
            new InventoryTask($messageDispatcher, $runService),
            $logCleaner ?? static::createStub(LogCleaner::class),
            $runService,
            $syncResetter ?? static::createStub(SyncResetter::class),
            $productSelection ?? static::createStub(ProductSelection::class),
        );
    }
}
