<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Product;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Inventory\RemoteCalculator;
use Swag\PayPal\IZettle\MessageQueue\Handler\Sync\InventorySyncHandler;
use Swag\PayPal\IZettle\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\Inventory\LocalUpdater;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;
use Swag\PayPal\Test\IZettle\Helper\SalesChannelTrait;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\ChangeBulkInventoryFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\IZettleClientFactoryMock;
use Swag\PayPal\Test\IZettle\Mock\MessageBusMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleInventoryRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelProductRepoMock;

class CompleteInventoryTest extends TestCase
{
    use KernelTestBehaviour;
    use SalesChannelTrait;

    public function testInventorySync(): void
    {
        $inventoryResource = new InventoryResource(new IZettleClientFactoryMock());
        $inventoryRepository = new IZettleInventoryRepoMock();
        $productRepository = new ProductRepoMock();
        $salesChannelProductRepository = new SalesChannelProductRepoMock();

        $inventoryContextFactory = new InventoryContextFactory(
            $inventoryResource,
            new UuidConverter(),
            $inventoryRepository
        );

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get('Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory');

        $messageBus = new MessageBusMock();

        $inventorySyncManager = new InventorySyncManager(
            $messageBus,
            new ProductSelection(
                $salesChannelProductRepository,
                $this->createMock(ProductStreamBuilder::class),
                $salesChannelContextFactory
            ),
            $salesChannelProductRepository,
            $inventoryContextFactory
        );

        $inventorySyncHandler = new InventorySyncHandler(
            new RunService(
                $this->createMock(EntityRepositoryInterface::class),
                $this->createMock(EntityRepositoryInterface::class),
                new Logger('test')
            ),
            new NullLogger(),
            $productRepository,
            $inventoryContextFactory,
            new InventorySyncer(
                $inventoryContextFactory,
                new LocalUpdater(
                    $productRepository,
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
        ];

        $inventoryContext = $inventoryContextFactory->getContext($salesChannel, $context);
        $inventoryContext->setProductIds($productIds);
        $inventoryContextFactory->updateLocal($inventoryContext);

        /*
         * A - unchanged
         * B - new
         * C - changed online
         * D - changed local
         * E - changed both sides
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

        $inventoryRepository->createMockEntity($productA, Defaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productC, Defaults::SALES_CHANNEL, 1);
        $inventoryRepository->createMockEntity($productD, Defaults::SALES_CHANNEL, 3);
        $inventoryRepository->createMockEntity($productE, Defaults::SALES_CHANNEL, 4);

        $inventorySyncManager->buildMessages($salesChannel, $context, Uuid::randomHex());
        $messageBus->execute([$inventorySyncHandler]);

        // product B added
        static::assertSame(5, $inventoryRepository->search(new Criteria(), $context)->getTotal());

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

        // stock updated in product
        static::assertSame(2, $productA->getStock());
        static::assertSame(2, $productB->getStock());
        static::assertSame(2, $productC->getStock());
        static::assertSame(3, $productD->getStock());
        static::assertSame(2, $productE->getStock());

        // inventory updated online
        static::assertTrue(ChangeBulkInventoryFixture::$called);
    }
}
