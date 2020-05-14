<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Sync\Context\InventoryContext;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\Inventory\RemoteUpdater;

class InventorySyncer
{
    /**
     * @var ProductSelection
     */
    private $productSelection;

    /**
     * @var InventoryContextFactory
     */
    private $inventoryContextFactory;

    /**
     * @var RemoteUpdater
     */
    private $remoteUpdater;

    /**
     * @var EntityRepositoryInterface
     */
    private $inventoryRepository;

    public function __construct(
        ProductSelection $productSelection,
        InventoryContextFactory $inventoryContextFactory,
        RemoteUpdater $remoteUpdater,
        EntityRepositoryInterface $inventoryRepository
    ) {
        $this->productSelection = $productSelection;
        $this->inventoryContextFactory = $inventoryContextFactory;
        $this->remoteUpdater = $remoteUpdater;
        $this->inventoryRepository = $inventoryRepository;
    }

    public function syncInventory(
        IZettleSalesChannelEntity $iZettleSalesChannel,
        Context $context,
        ?ProductCollection $productCollection = null
    ): void {
        if ($productCollection === null) {
            $productCollection = $this->productSelection->getProducts($iZettleSalesChannel, $context, false);
        }

        $inventoryContext = $this->inventoryContextFactory->getContext($iZettleSalesChannel, $context);

        $changes = $this->remoteUpdater->updateRemote($productCollection, $inventoryContext);

        if ($changes->count() > 0) {
            $this->updateLocalChanges($changes, $inventoryContext);
        }
    }

    protected function updateLocalChanges(ProductCollection $productCollection, InventoryContext $inventoryContext): void
    {
        $localChanges = [];
        foreach ($productCollection->getElements() as $productEntity) {
            $localChanges[] = [
                'salesChannelId' => $inventoryContext->getIZettleSalesChannel()->getSalesChannelId(),
                'productId' => $productEntity->getId(),
                'productVersionId' => $productEntity->getVersionId(),
                'stock' => $productEntity->getAvailableStock(),
            ];
        }
        $this->inventoryRepository->upsert($localChanges, $inventoryContext->getContext());

        $this->inventoryContextFactory->updateContext($inventoryContext);
    }
}
