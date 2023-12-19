<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Sync\Inventory\Calculator\LocalCalculator;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;

/**
 * @internal
 */
#[Package('checkout')]
class LocalUpdaterTest extends TestCase
{
    use UpdaterTrait;

    private EntityRepository&MockObject $productRepository;

    private MockObject $logger;

    private LocalUpdater $localUpdater;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(EntityRepository::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->localUpdater = new LocalUpdater(
            $this->productRepository,
            new LocalCalculator(),
            $this->logger
        );
    }

    #[DataProvider('dataProviderInventoryUpdate')]
    public function testUpdateLocalInventory(int $localInventory, int $posInventory, int $change): void
    {
        $product = $this->getVariantProduct();

        $inventoryContext = $this->createInventoryContext($product, $localInventory, $posInventory);

        $this->productRepository->expects($change === 0 ? static::never() : static::once())
            ->method('update')
            ->with([[
                'id' => $product->getId(),
                'versionId' => $product->getVersionId(),
                'stock' => $product->getStock() + $change,
            ]]);

        $this->logger->expects($change === 0 ? static::never() : static::once())
            ->method('info');

        $this->localUpdater->updateLocal(new ProductCollection([$product]), $inventoryContext);
    }

    public function testUpdateLocalInventoryWithParentProduct(): void
    {
        $product = $this->getParentProduct();

        $inventoryContext = $this->createInventoryContext($product, 1, 2);

        $this->productRepository->expects(static::never())->method('update');

        $this->localUpdater->updateLocal(new ProductCollection([$product]), $inventoryContext);
    }
}
