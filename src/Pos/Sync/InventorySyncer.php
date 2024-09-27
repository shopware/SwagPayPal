<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\Inventory\LocalUpdater;
use Swag\PayPal\Pos\Sync\Inventory\RemoteUpdater;
use Swag\PayPal\Pos\Sync\Inventory\StockChange;

#[Package('checkout')]
class InventorySyncer
{
    private InventoryContextFactory $inventoryContextFactory;

    private LocalUpdater $localUpdater;

    private RemoteUpdater $remoteUpdater;

    private EntityRepository $inventoryRepository;

    /**
     * @internal
     */
    public function __construct(
        InventoryContextFactory $inventoryContextFactory,
        LocalUpdater $localUpdater,
        RemoteUpdater $remoteUpdater,
        EntityRepository $inventoryRepository,
    ) {
        $this->inventoryContextFactory = $inventoryContextFactory;
        $this->localUpdater = $localUpdater;
        $this->remoteUpdater = $remoteUpdater;
        $this->inventoryRepository = $inventoryRepository;
    }

    /**
     * @param ProductCollection $entityCollection
     */
    public function sync(
        EntityCollection $entityCollection,
        InventoryContext $inventoryContext,
    ): void {
        $changes = $this->remoteUpdater->updateRemote($entityCollection, $inventoryContext);
        $this->updateLocalChanges($changes, $inventoryContext);

        $changes = $this->localUpdater->updateLocal($entityCollection, $inventoryContext);
        $this->updateLocalChanges($changes, $inventoryContext);
    }

    public function updateLocalChanges(ProductCollection $productCollection, InventoryContext $inventoryContext): void
    {
        if ($productCollection->count() === 0) {
            return;
        }

        $localChanges = [];
        foreach ($productCollection->getElements() as $productEntity) {
            /** @var StockChange|null $stockChange */
            $stockChange = $productEntity->getExtension(StockChange::STOCK_CHANGE_EXTENSION);

            if ($stockChange === null) {
                continue;
            }

            $localChanges[] = [
                'salesChannelId' => $inventoryContext->getSalesChannel()->getId(),
                'productId' => $productEntity->getId(),
                'productVersionId' => $productEntity->getVersionId(),
                'stock' => $inventoryContext->getLocalInventory($productEntity) + $stockChange->getStockChange(),
            ];

            $productEntity->removeExtension(StockChange::STOCK_CHANGE_EXTENSION);
        }
        $this->inventoryRepository->upsert($localChanges, $inventoryContext->getContext());

        $this->inventoryContextFactory->updateLocal($inventoryContext);
    }
}
