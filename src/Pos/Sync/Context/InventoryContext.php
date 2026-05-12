<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Context;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\JsonSerializableTrait;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\Inventory\Status\Variant;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryEntity;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class InventoryContext implements \JsonSerializable
{
    use JsonSerializableTrait {
        jsonSerialize as traitJsonSerialize;
    }

    private PosSalesChannelInventoryCollection $localInventory;

    private UuidConverter $uuidConverter;

    private SalesChannelEntity $salesChannel;

    private Context $context;

    private Status $remoteInventory;

    private string $storeUuid;

    private string $supplierUuid;

    private string $binUuid;

    private string $soldUuid;

    /**
     * @var string[]|null
     */
    private ?array $productIds;

    public function __construct(
        string $storeUuid,
        string $supplierUuid,
        string $binUuid,
        string $soldUuid,
        Status $remoteInventory,
    ) {
        $this->storeUuid = $storeUuid;
        $this->supplierUuid = $supplierUuid;
        $this->binUuid = $binUuid;
        $this->soldUuid = $soldUuid;
        $this->remoteInventory = $remoteInventory;
        $this->uuidConverter = new UuidConverter();
        $this->localInventory = new PosSalesChannelInventoryCollection();
        $this->context = Context::createDefaultContext();
        $this->productIds = null;
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

        if (!$ignoreTracking && !$this->isTracked($productEntity)) {
            return null;
        }

        $variant = $this->findRemoteInventory($productUuid, $variantUuid);

        if ($variant === null) {
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
            static function (PosSalesChannelInventoryEntity $entity) use ($productEntity) {
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

    public function setSalesChannel(SalesChannelEntity $salesChannel): void
    {
        $this->salesChannel = $salesChannel;
    }

    public function getPosSalesChannel(): PosSalesChannelEntity
    {
        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $this->salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        return $posSalesChannel;
    }

    public function addLocalInventory(PosSalesChannelInventoryCollection $localInventory): void
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

    public function jsonSerialize(): array
    {
        $value = $this->traitJsonSerialize();

        unset(
            $value['context'],
            $value['localInventory'],
            $value['salesChannel'],
            $value['uuidConverter'],
        );

        return $value;
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
