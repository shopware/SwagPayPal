<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Context;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityRepositoryNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelInventoryCollection;
use Swag\PayPal\Pos\Resource\InventoryResource;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class InventoryContextFactory
{
    private InventoryResource $inventoryResource;

    private UuidConverter $uuidConverter;

    private EntityRepository $inventoryRepository;

    /**
     * @internal
     */
    public function __construct(
        InventoryResource $inventoryResource,
        UuidConverter $uuidConverter,
        EntityRepository $inventoryRepository,
    ) {
        $this->inventoryResource = $inventoryResource;
        $this->uuidConverter = $uuidConverter;
        $this->inventoryRepository = $inventoryRepository;
    }

    public function getContext(SalesChannelEntity $salesChannel): InventoryContext
    {
        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        $locations = $this->loadLocations($posSalesChannel);
        $remoteInventory = $this->inventoryResource->getInventory(
            $posSalesChannel,
            $locations['STORE']
        );

        $context = new InventoryContext(
            $locations['STORE'],
            $locations['SUPPLIER'],
            $locations['BIN'],
            $locations['SOLD'],
            $remoteInventory,
        );
        $context->setSalesChannel($salesChannel);

        return $context;
    }

    public function filterContext(InventoryContext $inventoryContext, array $productIds, array $parentIds): InventoryContext
    {
        $newInventoryContext = clone $inventoryContext;

        $convertedProductIds = \array_unique(\array_map([$this->uuidConverter, 'convertUuidToV1'], $productIds));
        $trackedProductIds = \array_unique(\array_merge($convertedProductIds, \array_map([$this->uuidConverter, 'convertUuidToV1'], $parentIds)));

        $status = new Status();
        $status->setTrackedProducts(\array_intersect($trackedProductIds, $inventoryContext->getRemoteInventory()->getTrackedProducts()));
        $status->setVariants(\array_filter(
            $inventoryContext->getRemoteInventory()->getVariants(),
            static function ($variant) use ($convertedProductIds) {
                return \in_array($variant->getProductUuid(), $convertedProductIds, true)
                    || \in_array($variant->getVariantUuid(), $convertedProductIds, true);
            }
        ));

        $newInventoryContext->setRemoteInventory($status);
        $newInventoryContext->setProductIds($productIds);

        return $newInventoryContext;
    }

    public function updateLocal(InventoryContext $inventoryContext): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $inventoryContext->getSalesChannel()->getId()));
        $productIds = $inventoryContext->getProductIds();
        if ($productIds !== null) {
            $criteria->addFilter(new EqualsAnyFilter('productId', $productIds));
        }
        $inventory = $this->inventoryRepository->search($criteria, $inventoryContext->getContext())->getEntities();
        if (!($inventory instanceof PosSalesChannelInventoryCollection)) {
            throw new EntityRepositoryNotFoundException('swag_paypal_pos_sales_channel_inventory');
        }

        $inventoryContext->addLocalInventory($inventory);
    }

    private function loadLocations(PosSalesChannelEntity $posSalesChannel): array
    {
        $locations = $this->inventoryResource->getLocations($posSalesChannel);

        $locationData = [];
        foreach ($locations as $location) {
            $locationData[$location->getType()] = $location->getUuid();
        }

        return $locationData;
    }
}
