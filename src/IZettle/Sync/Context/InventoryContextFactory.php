<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Context;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\EntityRepositoryNotFoundException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryCollection;
use Swag\PayPal\IZettle\Resource\InventoryResource;
use Swag\PayPal\SwagPayPal;

class InventoryContextFactory
{
    /**
     * @var InventoryResource
     */
    private $inventoryResource;

    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    /**
     * @var EntityRepositoryInterface
     */
    private $inventoryRepository;

    public function __construct(
        InventoryResource $inventoryResource,
        UuidConverter $uuidConverter,
        EntityRepositoryInterface $inventoryRepository
    ) {
        $this->inventoryResource = $inventoryResource;
        $this->uuidConverter = $uuidConverter;
        $this->inventoryRepository = $inventoryRepository;
    }

    public function getContext(SalesChannelEntity $salesChannel, Context $context): InventoryContext
    {
        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $locations = $this->loadLocations($iZettleSalesChannel);
        $remoteInventory = $this->inventoryResource->getInventory(
            $iZettleSalesChannel,
            $locations['STORE']
        );

        $inventoryContext = new InventoryContext(
            $this->uuidConverter,
            $salesChannel,
            $locations['STORE'],
            $locations['SUPPLIER'],
            $locations['BIN'],
            $locations['SOLD'],
            $remoteInventory,
            new IZettleSalesChannelInventoryCollection(),
            $context
        );

        return $inventoryContext;
    }

    public function filterContext(InventoryContext $inventoryContext, array $productIds, array $parentIds): InventoryContext
    {
        $newInventoryContext = clone $inventoryContext;

        $convertedProductIds = \array_map([$this->uuidConverter, 'convertUuidToV1'], $productIds);
        $trackedProductIds = \array_merge($convertedProductIds, \array_map([$this->uuidConverter, 'convertUuidToV1'], $parentIds));

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
        if (!($inventory instanceof IZettleSalesChannelInventoryCollection)) {
            throw new EntityRepositoryNotFoundException('swag_paypal_izettle_sales_channel_inventory');
        }

        $inventoryContext->addLocalInventory($inventory);
    }

    private function loadLocations(IZettleSalesChannelEntity $iZettleSalesChannel): array
    {
        $locations = $this->inventoryResource->getLocations($iZettleSalesChannel);

        $locationData = [];
        foreach ($locations as $location) {
            $locationData[$location->getType()] = $location->getUuid();
        }

        return $locationData;
    }
}
