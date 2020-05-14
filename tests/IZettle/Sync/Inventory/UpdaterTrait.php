<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\IZettle\Sync\Inventory;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\Inventory\Status\Variant;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryEntity;

trait UpdaterTrait
{
    public function dataProviderInventoryUpdate(): array
    {
        return [
            [1, 2, 1],  // more
            [3, 1, -2], // less
            [2, 2, 0],  // equal
            [1, -3, -4], // negative
        ];
    }

    private function getParentProduct(): ProductEntity
    {
        $product = new ProductEntity();
        $product->setId('4191c1b4c6af4f5782a7604aa9ae3222');
        $product->setVersionId('7c1da595-2b4c-4c25-afa7-8dcf5d3adca0');
        $product->setChildCount(2);
        $product->setStock(1);
        $product->setAvailableStock(1);

        return $product;
    }

    private function setLocalInventory(ProductEntity $product, int $stock): void
    {
        $localInventory = new IZettleSalesChannelInventoryEntity();
        $localInventory->setSalesChannelId(Defaults::SALES_CHANNEL);
        $localInventory->setProductId($product->getId());
        $localInventory->setProductVersionId((string) $product->getVersionId());
        $localInventory->setUniqueIdentifier(Uuid::randomHex());
        $localInventory->setStock($stock);
        $this->inventoryContext->setLocalInventory(new IZettleSalesChannelInventoryCollection([
            $localInventory,
        ]));
    }

    private function setIZettleInventory(ProductEntity $product, int $stock): void
    {
        $status = new Status();
        $uuidConverter = new UuidConverter();

        $status->addVariant();
        $status->setTrackedProducts([
            $uuidConverter->convertUuidToV1($product->getParentId() ?? $product->getId()),
        ]);
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $uuidConverter->convertUuidToV1((string) $product->getParentId()),
            'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'balance' => (string) $stock,
        ]);
        $status->addVariant($variant);

        $this->inventoryContext->setIZettleInventory($status);
    }
}
