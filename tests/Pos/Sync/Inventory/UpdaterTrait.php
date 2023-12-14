<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Sync\Inventory;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\Inventory\Status\Variant;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryEntity;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;

/**
 * @internal
 */
#[Package('checkout')]
trait UpdaterTrait
{
    use InventoryTrait;

    public static function dataProviderInventoryUpdate(): array
    {
        return [
            [1, 2, 1],  // more
            [3, 1, -2], // less
            [2, 2, 0],  // equal
            [1, -3, -4], // negative
        ];
    }

    private function getParentProduct(): SalesChannelProductEntity
    {
        $product = new SalesChannelProductEntity();
        $product->setId('4191c1b4c6af4f5782a7604aa9ae3222');
        $product->setVersionId('7c1da595-2b4c-4c25-afa7-8dcf5d3adca0');
        $product->setChildCount(2);
        $product->setStock(1);
        $product->setAvailableStock(1);

        return $product;
    }

    private function createInventoryContext(SalesChannelProductEntity $product, int $localStock, int $posStock): InventoryContext
    {
        $localInventory = new PosSalesChannelInventoryEntity();
        $localInventory->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $localInventory->setProductId($product->getId());
        $localInventory->setProductVersionId((string) $product->getVersionId());
        $localInventory->setUniqueIdentifier(Uuid::randomHex());
        $localInventory->setStock($localStock);

        $status = new Status();
        $uuidConverter = new UuidConverter();

        $status->addVariant();
        $status->assign(['trackedProducts' => [
            $uuidConverter->convertUuidToV1($product->getParentId() ?? $product->getId()),
        ]]);
        $variant = new Variant();
        $variant->assign([
            'productUuid' => $product->getParentId() ? $uuidConverter->convertUuidToV1((string) $product->getParentId()) : '',
            'variantUuid' => $uuidConverter->convertUuidToV1($product->getId()),
            'balance' => (string) $posStock,
        ]);
        $status->addVariant($variant);

        $context = new InventoryContext(
            $this->locations['STORE'],
            $this->locations['SUPPLIER'],
            $this->locations['BIN'],
            $this->locations['SOLD'],
            $status,
        );
        $context->setSalesChannel($this->getSalesChannel(Context::createDefaultContext()));
        $context->addLocalInventory(new PosSalesChannelInventoryCollection([$localInventory]));

        return $context;
    }
}
