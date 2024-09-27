<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory\Calculator;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges\ProductChange\VariantChange;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;

#[Package('checkout')]
class RemoteCalculator
{
    private UuidConverter $uuidConverter;

    /**
     * @internal
     */
    public function __construct(UuidConverter $uuidConverter)
    {
        $this->uuidConverter = $uuidConverter;
    }

    public function calculateRemoteChange(
        ProductEntity $productEntity,
        InventoryContext $inventoryContext,
    ): ?ProductChange {
        $currentStock = $productEntity->getAvailableStock();
        $previousStock = $inventoryContext->getLocalInventory($productEntity);

        $isTracked = $inventoryContext->isTracked($productEntity);

        if ($previousStock === null || !$isTracked) {
            $previousStock = $inventoryContext->getSingleRemoteInventory($productEntity, true);
        }

        $difference = $currentStock - $previousStock;

        if ($difference === 0 && $isTracked) {
            return null;
        }

        $productUuid = $this->uuidConverter->convertUuidToV1($productEntity->getParentId() ?? $productEntity->getId());

        $productChange = new ProductChange();
        $productChange->setProductUuid($productUuid);
        $productChange->setTrackingStatusChange($isTracked ? ProductChange::TRACKING_NOCHANGE : ProductChange::TRACKING_START);

        if ($difference === 0) {
            return $productChange;
        }

        $variantChange = new VariantChange();
        $variantChange->setProductUuid($productUuid);
        $variantUuid = $productEntity->getId();
        if ($productEntity->getParentId() === null) {
            $variantUuid = $this->uuidConverter->incrementUuid($variantUuid);
        }
        $variantChange->setVariantUuid($this->uuidConverter->convertUuidToV1($variantUuid));

        if ($difference > 0) {
            $variantChange->setFromLocationUuid($inventoryContext->getSupplierUuid());
            $variantChange->setToLocationUuid($inventoryContext->getStoreUuid());
            $variantChange->setChange($difference);
        } else {
            $variantChange->setFromLocationUuid($inventoryContext->getStoreUuid());
            $variantChange->setToLocationUuid($inventoryContext->getBinUuid());
            $variantChange->setChange(-$difference);
        }
        $productChange->addVariantChange($variantChange);

        return $productChange;
    }
}
