<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Context;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\Inventory\Status\Variant;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryEntity;
use Swag\PayPal\SwagPayPal;

class InventoryContext
{
    /**
     * @var IZettleSalesChannelInventoryCollection
     */
    private $localInventory;

    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    /**
     * @var SalesChannelEntity
     */
    private $salesChannel;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Status
     */
    private $remoteInventory;

    /**
     * @var string
     */
    private $storeUuid;

    /**
     * @var string
     */
    private $supplierUuid;

    /**
     * @var string
     */
    private $binUuid;

    /**
     * @var string
     */
    private $soldUuid;

    /**
     * @var string[]|null
     */
    private $productIds;

    public function __construct(
        UuidConverter $uuidConverter,
        SalesChannelEntity $salesChannel,
        string $storeUuid,
        string $supplierUuid,
        string $binUuid,
        string $soldUuid,
        Status $remoteInventory,
        IZettleSalesChannelInventoryCollection $localInventory,
        Context $context
    ) {
        $this->uuidConverter = $uuidConverter;
        $this->salesChannel = $salesChannel;
        $this->storeUuid = $storeUuid;
        $this->supplierUuid = $supplierUuid;
        $this->binUuid = $binUuid;
        $this->soldUuid = $soldUuid;
        $this->remoteInventory = $remoteInventory;
        $this->localInventory = $localInventory;
        $this->context = $context;
    }

    public function getSingleRemoteInventory(ProductEntity $productEntity, bool $ignoreTracking = false): ?int
    {
        $productUuid = $productEntity->getParentId();
        $variantUuid = $productEntity->getId();
        if ($productUuid === null) {
            $productUuid = $variantUuid;
            $variantUuid = $this->uuidConverter->incrementUuid($variantUuid);
        }
        $productUuid = $this->uuidConverter->convertUuidToV1($productUuid);
        $variantUuid = $this->uuidConverter->convertUuidToV1($variantUuid);

        $variant = $this->findRemoteInventory($productUuid, $variantUuid);

        if ($variant === null || !($ignoreTracking || $this->isTracked($productEntity))) {
            return null;
        }

        return $variant->getBalance();
    }

    public function isTracked(ProductEntity $productEntity): bool
    {
        $productUuid = $productEntity->getParentId() ?? $productEntity->getId();
        $productUuid = $this->uuidConverter->convertUuidToV1($productUuid);

        return \in_array($productUuid, $this->remoteInventory->getTrackedProducts(), true);
    }

    public function getLocalInventory(ProductEntity $productEntity): ?int
    {
        $inventory = $this->localInventory->filter(
            static function (IZettleSalesChannelInventoryEntity $entity) use ($productEntity) {
                return $entity->getProductId() === $productEntity->getId()
                    && $entity->getProductVersionId() === $productEntity->getVersionId();
            }
        );

        $inventoryEntry = $inventory->first();
        if ($inventoryEntry === null) {
            return null;
        }

        return $inventoryEntry->getStock();
    }

    public function getStoreUuid(): string
    {
        return $this->storeUuid;
    }

    public function getSupplierUuid(): string
    {
        return $this->supplierUuid;
    }

    public function getBinUuid(): string
    {
        return $this->binUuid;
    }

    public function getSoldUuid(): string
    {
        return $this->soldUuid;
    }

    public function addRemoteInventory(Variant ...$newVariants): void
    {
        foreach ($newVariants as $newVariant) {
            $variant = $this->findRemoteInventory($newVariant->getProductUuid(), $newVariant->getVariantUuid());
            if ($variant !== null) {
                $variant->setBalance((string) $newVariant->getBalance());

                continue;
            }
            $this->remoteInventory->addVariant($newVariant);
        }
    }

    public function setRemoteInventory(Status $remoteInventory): void
    {
        $this->remoteInventory = $remoteInventory;
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getIZettleSalesChannel(): IZettleSalesChannelEntity
    {
        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $this->salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        return $iZettleSalesChannel;
    }

    public function addLocalInventory(IZettleSalesChannelInventoryCollection $localInventory): void
    {
        foreach ($localInventory->getElements() as $element) {
            $this->localInventory->add($element);
        }
    }

    public function getRemoteInventory(): Status
    {
        return $this->remoteInventory;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return string[]|null
     */
    public function getProductIds(): ?array
    {
        return $this->productIds;
    }

    /**
     * @param string[] $productIds
     */
    public function setProductIds(array $productIds): void
    {
        $this->productIds = $productIds;
    }

    private function findRemoteInventory(string $productUuid, string $variantUuid): ?Variant
    {
        foreach ($this->remoteInventory->getVariants() as $variant) {
            if ($variant->getProductUuid() === $productUuid && $variant->getVariantUuid() === $variantUuid) {
                return $variant;
            }
        }

        return null;
    }
}
