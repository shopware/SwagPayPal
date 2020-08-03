<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\Sync\Context\InventoryContext;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\Inventory\LocalUpdater;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;

class InventorySyncer extends AbstractSyncer
{
    /**
     * @var InventoryContextFactory
     */
    private $inventoryContextFactory;

    /**
     * @var LocalUpdater
     */
    private $localUpdater;

    /**
     * @var RemoteUpdater
     */
    private $remoteUpdater;

    /**
     * @var EntityRepositoryInterface
     */
    private $inventoryRepository;

    public function __construct(
        InventoryContextFactory $inventoryContextFactory,
        LocalUpdater $localUpdater,
        RemoteUpdater $remoteUpdater,
        EntityRepositoryInterface $inventoryRepository
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
        InventoryContext $inventoryContext
    ): void {
        $changes = $this->remoteUpdater->updateRemote($entityCollection, $inventoryContext);
        $this->updateLocalChanges($changes, $inventoryContext);

        $changes = $this->localUpdater->updateLocal($entityCollection, $inventoryContext);
        $this->updateLocalChanges($changes, $inventoryContext);
    }

    private function updateLocalChanges(ProductCollection $productCollection, InventoryContext $inventoryContext): void
    {
        if ($productCollection->count() === 0) {
            return;
        }

        $localChanges = [];
        foreach ($productCollection->getElements() as $productEntity) {
            $localChanges[] = [
                'salesChannelId' => $inventoryContext->getSalesChannel()->getId(),
                'productId' => $productEntity->getId(),
                'productVersionId' => $productEntity->getVersionId(),
                'stock' => $productEntity->getAvailableStock(),
            ];
        }
        $this->inventoryRepository->upsert($localChanges, $inventoryContext->getContext());

        $this->inventoryContextFactory->updateLocal($inventoryContext);
    }
}
