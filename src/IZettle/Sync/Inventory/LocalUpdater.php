<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Inventory;

use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdater;
use Shopware\Core\Content\Product\ProductCollection;
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

    public function __construct(
        EntityRepositoryInterface $productRepository,
        StockUpdater $stockUpdater
    ) {
        $this->productRepository = $productRepository;
        $this->stockUpdater = $stockUpdater;
    }

    public function updateLocal(ProductCollection $productCollection, InventoryContext $inventoryContext): ProductCollection
    {
        $productChanges = [];
        $changedProducts = new ProductCollection();
        foreach ($productCollection->getElements() as $productEntity) {
            if ($productEntity->getChildCount() > 0) {
                continue;
            }

            $previousStock = $inventoryContext->getLocalInventory($productEntity);
            $currentIZettleStock = $inventoryContext->getIZettleInventory($productEntity);

            $stockChange = $currentIZettleStock - $previousStock;

            if ($stockChange === 0) {
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

        $this->stockUpdater->update($changedProducts->getKeys(), $inventoryContext->getContext());

        return $changedProducts;
    }
}
