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

    /**
     * @var InventoryContext[]
     */
    private $inventoryContexts = [];

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
        if (isset($this->inventoryContexts[$salesChannel->getId()])) {
            return $this->inventoryContexts[$salesChannel->getId()];
        }

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $locations = $this->loadLocations($iZettleSalesChannel);
        $iZettleInventory = $this->loadIZettleInventory($iZettleSalesChannel, $locations['STORE']);
        $localInventory = $this->loadLocalInventory($iZettleSalesChannel->getSalesChannelId(), $context);

        $inventoryContext = new InventoryContext(
            $this->inventoryResource,
            $this->uuidConverter,
            $salesChannel,
            $locations['STORE'],
            $locations['SUPPLIER'],
            $locations['BIN'],
            $locations['SOLD'],
            $iZettleInventory,
            $localInventory,
            $context
        );

        $this->inventoryContexts[$salesChannel->getId()] = $inventoryContext;

        return $inventoryContext;
    }

    public function updateContext(InventoryContext $inventoryContext): void
    {
        $inventoryContext->updateLocalInventory($this->loadLocalInventory(
            $inventoryContext->getSalesChannel()->getId(),
            $inventoryContext->getContext()
        ));
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

    private function loadIZettleInventory(IZettleSalesChannelEntity $iZettleSalesChannel, string $storeUuid): Status
    {
        return $this->inventoryResource->getInventory(
            $iZettleSalesChannel,
            $storeUuid
        );
    }

    private function loadLocalInventory(string $salesChannelId, Context $context): IZettleSalesChannelInventoryCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $inventory = $this->inventoryRepository->search($criteria, $context)->getEntities();
        if (!($inventory instanceof IZettleSalesChannelInventoryCollection)) {
            throw new EntityRepositoryNotFoundException('swag_paypal_izettle_sales_channel_inventory');
        }

        return $inventory;
    }
}
