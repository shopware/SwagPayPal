<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Inventory;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\Sync\Context\InventoryContext;

class LocalUpdater
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StockUpdater
     */
    private $stockUpdater;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        StockUpdater $stockUpdater,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->stockUpdater = $stockUpdater;
        $this->logger = $logger;
    }

    public function updateLocal(ProductCollection $productCollection, InventoryContext $inventoryContext): ProductCollection
    {
        $productChanges = [];
        $changedProducts = new ProductCollection();
        foreach ($productCollection->getElements() as $productEntity) {
            if ($productEntity->getChildCount() > 0) {
                continue;
            }

            $stockChange = $this->getChangeAmount($productEntity, $inventoryContext);

            if ($stockChange === 0 || $inventoryContext->getSingleRemoteInventory($productEntity) === null) {
                continue;
            }

            $productEntity->setAvailableStock($productEntity->getAvailableStock() + $stockChange);
            $changedProducts->add($productEntity);
            $productChanges[] = [
                'id' => $productEntity->getId(),
                'versionId' => $productEntity->getVersionId(),
                'stock' => $productEntity->getStock() + $stockChange,
            ];
        }

        if (\count($changedProducts) === 0) {
            return $changedProducts;
        }

        $this->productRepository->update($productChanges, $inventoryContext->getContext());

        foreach ($changedProducts as $changedProduct) {
            $changeAmount = $this->getChangeAmount($changedProduct, $inventoryContext);

            $this->logger->info('Changed local inventory of {productName} by {change}', [
                'product' => $changedProduct,
                'productName' => $changedProduct->getName() ?? 'variant',
                'change' => $changeAmount,
            ]);
        }

        $this->stockUpdater->update($changedProducts->getKeys(), $inventoryContext->getContext());

        return $changedProducts;
    }

    private function getChangeAmount(ProductEntity $productEntity, InventoryContext $inventoryContext): int
    {
        $previousStock = $inventoryContext->getLocalInventory($productEntity);

        if ($previousStock === null) {
            return 0;
        }

        $currentIZettleStock = $inventoryContext->getSingleRemoteInventory($productEntity);

        return $currentIZettleStock - $previousStock;
    }
}
