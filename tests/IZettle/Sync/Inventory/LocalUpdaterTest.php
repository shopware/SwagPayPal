<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Swag\PayPal\IZettle\Sync\Inventory\Calculator\LocalCalculator;
use Swag\PayPal\IZettle\Sync\Inventory\LocalUpdater;

class LocalUpdaterTest extends TestCase
{
    use UpdaterTrait;

    /**
     * @var MockObject
     */
    private $productRepository;

    /**
     * @var MockObject
     */
    private $stockUpdater;

    /**
     * @var MockObject
     */
    private $logger;

    /**
     * @var LocalUpdater
     */
    private $localUpdater;

    public function setUp(): void
    {
        $this->productRepository = $this->createMock(EntityRepository::class);

        $this->stockUpdater = $this->createMock(StockUpdater::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->localUpdater = new LocalUpdater(
            $this->productRepository,
            new LocalCalculator(),
            $this->stockUpdater,
            $this->logger
        );
    }

    /**
     * @dataProvider dataProviderInventoryUpdate
     */
    public function testUpdateLocalInventory(int $localInventory, int $iZettleInventory, int $change): void
    {
        $product = $this->getVariantProduct();

        $inventoryContext = $this->createInventoryContext($product, $localInventory, $iZettleInventory);

        $this->productRepository->expects($change === 0 ? static::never() : static::once())
                                ->method('update')
                                ->with([[
                                    'id' => $product->getId(),
                                    'versionId' => $product->getVersionId(),
                                    'stock' => $product->getStock() + $change,
                                ]]);

        $this->stockUpdater->expects($change === 0 ? static::never() : static::once())
                           ->method('update')
                           ->with([$product->getId()]);

        $this->logger->expects($change === 0 ? static::never() : static::once())
                     ->method('info');

        $this->localUpdater->updateLocal(new ProductCollection([$product]), $inventoryContext);
    }

    public function testUpdateLocalInventoryWithParentProduct(): void
    {
        $product = $this->getParentProduct();

        $inventoryContext = $this->createInventoryContext($product, 1, 2);

        $this->productRepository->expects(static::never())->method('update');

        $this->stockUpdater->expects(static::never())->method('update');

        $this->localUpdater->updateLocal(new ProductCollection([$product]), $inventoryContext);
    }
}
