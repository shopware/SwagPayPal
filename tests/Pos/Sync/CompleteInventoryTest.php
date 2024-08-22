<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\MessageQueue\Handler\Sync\InventorySyncHandler;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalCalculator;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\RemoteCalculator;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;
use Swag\PayPal\Pos\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\Pos\Sync\InventorySyncer;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\Test\Pos\ConstantsForTesting;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\ChangeBulkInventoryFixture;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\MessageBusMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\PosInventoryRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunLogRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\RunRepoMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelProductRepoMock;
use Swag\PayPal\Test\Pos\Mock\RunServiceMock;

/**
 * @internal
 */
#[Package('checkout')]
class CompleteInventoryTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    public function testInventorySync(): void
    {
        $inventoryResource = new InventoryResource(new PosClientFactoryMock());
        $inventoryRepository = new PosInventoryRepoMock();
        $productRepository = new ProductRepoMock();
        $salesChannelProductRepository = new SalesChannelProductRepoMock();

        $inventoryContextFactory = new InventoryContextFactory(
            $inventoryResource,
            new UuidConverter(),
            $inventoryRepository
        );

        $messageBus = new MessageBusMock();
        $messageDispatcher = new MessageDispatcher($messageBus, $this->createMock(Connection::class));
        $messageHydrator = new MessageHydrator($this->createMock(SalesChannelContextService::class), $this->createMock(EntityRepository::class));

        $inventorySyncManager = new InventorySyncManager(
            $messageDispatcher,
            new ProductSelection(
                $salesChannelProductRepository,
                $this->createMock(ProductStreamBuilder::class),
                $this->getContainer()->get(SalesChannelContextFactory::class)
            ),
            $salesChannelProductRepository,
            $inventoryContextFactory
        );

        $runService = new RunServiceMock(
            new RunRepoMock(),
            new RunLogRepoMock(),
            $this->createMock(Connection::class),
            new Logger('test')
        );

        $inventorySyncHandler = new InventorySyncHandler(
            $runService,
            new NullLogger(),
            $messageDispatcher,
            $messageHydrator,
            $productRepository,
            $inventoryContextFactory,
            new InventorySyncer(
                $inventoryContextFactory,
                new LocalUpdater(
                    $productRepository,
                    new LocalCalculator(),
                    $this->createMock(StockUpdater::class),
                    new NullLogger()
                ),
                new RemoteUpdater(
                    $inventoryResource,
                    new RemoteCalculator(
                        new UuidConverter()
                    ),
                    new NullLogger()
                ),
                $inventoryRepository
            )
        );

        $context = Context::createDefaultContext();

        $salesChannel = $this->getSalesChannel($context);

        $productIds = [
            ConstantsForTesting::PRODUCT_A_ID,
            ConstantsForTesting::PRODUCT_B_ID,
            ConstantsForTesting::PRODUCT_C_ID,
            ConstantsForTesting::PRODUCT_D_ID,
            ConstantsForTesting::PRODUCT_E_ID,
            ConstantsForTesting::PRODUCT_G_ID,
        ];

        $inventoryContext = $inventoryContextFactory->getContext($salesChannel);
        $inventoryContext->setProductIds($productIds);
        $inventoryContextFactory->updateLocal($inventoryContext);

        /*
         * A - unchanged
         * B - new
         * C - changed online
         * D - changed local
         * E - changed both sides
         * G - disabled tracking online afterwards
         */
        $productA = $productRepository->createMockEntity('productA', 2, 1, ConstantsForTesting::PRODUCT_A_ID);
        $salesChannelProductRepository->addMockEntity($productA);
        $productB = $productRepository->createMockEntity('productB', 2, 2, ConstantsForTesting::PRODUCT_B_ID);
        $salesChannelProductRepository->addMockEntity($productB);
        $productC = $productRepository->createMockEntity('productC', 3, 1, ConstantsForTesting::PRODUCT_C_ID);
        $salesChannelProductRepository->addMockEntity($productC);
        $productD = $productRepository->createMockEntity('productD', 3, 2, ConstantsForTesting::PRODUCT_D_ID);
        $salesChannelProductRepository->addMockEntity($productD);
        $productE = $productRepository->createMockEntity('productE', 3, 3, ConstantsForTesting::PRODUCT_E_ID);
        $salesChannelProductRepository->addMockEntity($productE);
        $productG = $productRepository->createMockEntity('productG', 3, 3, ConstantsForTesting::PRODUCT_G_ID);
        $salesChannelProductRepository->addMockEntity($productG);

        $inventoryRepository->createMockEntity($productA, TestDefaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productC, TestDefaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productD, TestDefaults::SALES_CHANNEL, 3);
        $inventoryRepository->createMockEntity($productE, TestDefaults::SALES_CHANNEL, 4);
        $inventoryRepository->createMockEntity($productG, TestDefaults::SALES_CHANNEL, 3);

        $runId = $runService->startRun(TestDefaults::SALES_CHANNEL, 'inventory', [], $context);
        $messages = $inventorySyncManager->createMessages($salesChannel, $context, $runId);
        $messageDispatcher->bulkDispatch($messages, $runId);
        $messageBus->execute([$inventorySyncHandler]);

        // product B added
        static::assertSame(6, $inventoryRepository->search(new Criteria(), $context)->getTotal());

        // inventories saved correctly
        $inventory = $inventoryRepository->filterByProduct($productA);
        static::assertNotNull($inventory);
        static::assertSame(1, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productB);
        static::assertNotNull($inventory);
        static::assertSame(2, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productC);
        static::assertNotNull($inventory);
        static::assertSame(0, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productD);
        static::assertNotNull($inventory);
        static::assertSame(2, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productE);
        static::assertNotNull($inventory);
        static::assertSame(2, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productG);
        static::assertNotNull($inventory);
        static::assertSame(3, $inventory->getStock());

        // stock updated in product
        static::assertSame(2, $productA->getStock());
        static::assertSame(2, $productB->getStock());
        static::assertSame(2, $productC->getStock());
        static::assertSame(3, $productD->getStock());
        static::assertSame(2, $productE->getStock());
        static::assertSame(3, $productG->getStock());

        // inventory updated online
        static::assertTrue(ChangeBulkInventoryFixture::$called);
    }
}
