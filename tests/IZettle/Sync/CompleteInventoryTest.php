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
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Inventory\RemoteCalculator;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\IZettle\Run\RunService;
use Swag\PayPal\IZettle\Run\Task\InventoryTask;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\Inventory\LocalUpdater;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\IZettle\Sync\InventorySyncer;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\IZettle\ConstantsForTesting;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\ChangeInventoryFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\_fixtures\StartInventoryTrackingFixture;
use Swag\PayPal\Test\IZettle\Mock\Client\IZettleClientFactoryMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\IZettleInventoryRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\ProductRepoMock;
use Swag\PayPal\Test\IZettle\Mock\Repositories\SalesChannelProductRepoMock;

class CompleteInventoryTest extends TestCase
{
    use KernelTestBehaviour;

    private const DOMAIN = 'https://www.example.com/';

    public function testInventorySync(): void
    {
        $inventoryResource = new InventoryResource(new IZettleClientFactoryMock());
        $inventoryRepository = new IZettleInventoryRepoMock();
        $productRepository = new ProductRepoMock();
        $salesChannelProductRepository = new SalesChannelProductRepoMock();

        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get('Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory');

        $inventoryTask = new InventoryTask(
            new RunService(
                $this->createMock(EntityRepositoryInterface::class),
                new Logger('test')
            ),
            new NullLogger(),
            new InventorySyncer(
                new ProductSelection(
                    $salesChannelProductRepository,
                    $this->createMock(ProductStreamBuilder::class),
                    $salesChannelContextFactory
                ),
                new InventoryContextFactory(
                    $inventoryResource,
                    new UuidConverter(),
                    $inventoryRepository
                ),
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

        /** @var EntityRepositoryInterface $salesChannelRepository */
        $salesChannelRepository = $this->getContainer()->get('sales_channel.repository');
        $salesChannelCriteria = new Criteria([Defaults::SALES_CHANNEL]);

        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $salesChannelRepository->search($salesChannelCriteria, $context)->first();
        $iZettleSalesChannel = new IZettleSalesChannelEntity();
        $iZettleSalesChannel->setId(Uuid::randomHex());
        $iZettleSalesChannel->setSalesChannelId($salesChannel->getId());
        $iZettleSalesChannel->setMediaDomain(self::DOMAIN);
        $iZettleSalesChannel->setApiKey(ConstantsForTesting::VALID_API_KEY);
        $iZettleSalesChannel->setReplace(true);
        $iZettleSalesChannel->setSyncPrices(true);
        $iZettleSalesChannel->setProductStreamId(null);
        $salesChannel->setTypeId(SwagPayPal::SALES_CHANNEL_TYPE_IZETTLE);
        $salesChannel->addExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION, $iZettleSalesChannel);

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

        $inventoryTask->execute($salesChannel, $context);

        // product B added
        static::assertTrue(StartInventoryTrackingFixture::$called);
        static::assertEquals(5, $inventoryRepository->search(new Criteria(), $context)->getTotal());

        // inventories saved correctly
        $inventory = $inventoryRepository->filterByProduct($productA);
        static::assertNotNull($inventory);
        static::assertEquals(1, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productB);
        static::assertNotNull($inventory);
        static::assertEquals(2, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productC);
        static::assertNotNull($inventory);
        static::assertEquals(0, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productD);
        static::assertNotNull($inventory);
        static::assertEquals(2, $inventory->getStock());
        $inventory = $inventoryRepository->filterByProduct($productE);
        static::assertNotNull($inventory);
        static::assertEquals(2, $inventory->getStock());

        // stock updated in product
        static::assertEquals(2, $productA->getStock());
        static::assertEquals(2, $productB->getStock());
        static::assertEquals(2, $productC->getStock());
        static::assertEquals(3, $productD->getStock());
        static::assertEquals(2, $productE->getStock());

        // inventory updated online
        static::assertTrue(ChangeInventoryFixture::$called);
    }
}
