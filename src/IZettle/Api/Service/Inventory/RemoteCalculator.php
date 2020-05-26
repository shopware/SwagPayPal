<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Inventory;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Swag\PayPal\IZettle\Api\Inventory\Changes\Change;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Sync\Context\InventoryContext;

class RemoteCalculator
{
    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(UuidConverter $uuidConverter)
    {
        $this->uuidConverter = $uuidConverter;
    }

    public function calculateRemoteChange(
        ProductEntity $productEntity,
        InventoryContext $inventoryContext
    ): ?Change {
        $difference = $this->getChangeAmount($productEntity, $inventoryContext);

        if ($difference === 0) {
            return null;
        }

        $change = new Change();

        $productUuid = $productEntity->getParentId() ?? $productEntity->getId();
        $change->setProductUuid($this->uuidConverter->convertUuidToV1($productUuid));

        $variantUuid = $productEntity->getId();
        if ($productEntity->getParentId() === null) {
            $variantUuid = $this->uuidConverter->incrementUuid($variantUuid);
        }
        $change->setVariantUuid($this->uuidConverter->convertUuidToV1($variantUuid));

        if ($difference > 0) {
            $change->setFromLocationUuid($inventoryContext->getSupplierUuid());
            $change->setToLocationUuid($inventoryContext->getStoreUuid());
            $change->setChange($difference);
        } else {
            $change->setFromLocationUuid($inventoryContext->getStoreUuid());
            $change->setToLocationUuid($inventoryContext->getBinUuid());
            $change->setChange(-$difference);
        }

        return $change;
    }

    public function getChangeAmount(
        ProductEntity $productEntity,
        InventoryContext $inventoryContext
    ): int {
        $currentStock = $productEntity->getAvailableStock();

        if ($inventoryContext->isIZettleTracked($productEntity)) {
            $previousStock = $inventoryContext->getLocalInventory($productEntity);
        } else {
            $inventoryContext->startIZettleTracking($productEntity);
            $previousStock = $inventoryContext->getIZettleInventory($productEntity, true);
        }

        return $currentStock - $previousStock;
    }
}
