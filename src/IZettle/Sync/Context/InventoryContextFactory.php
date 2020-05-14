<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Context;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelInventoryCollection;
use Swag\PayPal\IZettle\Resource\InventoryResource;

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
        EntityRepositoryInterface $inventoryRespository
    ) {
        $this->inventoryResource = $inventoryResource;
        $this->uuidConverter = $uuidConverter;
        $this->inventoryRepository = $inventoryRespository;
    }

    public function getContext(IZettleSalesChannelEntity $iZettleSalesChannel, Context $context): InventoryContext
    {
        if (isset($this->inventoryContexts[$iZettleSalesChannel->getId()])) {
            return $this->inventoryContexts[$iZettleSalesChannel->getId()];
        }

        $inventoryContext = new InventoryContext(
            $this->inventoryResource,
            $this->uuidConverter,
            $iZettleSalesChannel,
            $context
        );

        $this->loadLocations($inventoryContext);
        $this->loadIZettleInventory($inventoryContext);
        $this->loadLocalInventory($inventoryContext);

        $this->inventoryContexts[$iZettleSalesChannel->getId()] = $inventoryContext;

        return $inventoryContext;
    }

    public function updateContext(InventoryContext $inventoryContext): void
    {
        $this->loadLocalInventory($inventoryContext);
    }

    protected function loadLocations(InventoryContext $inventoryContext): void
    {
        $locations = $this->inventoryResource->getLocations($inventoryContext->getIZettleSalesChannel());
        foreach ($locations as $location) {
            switch ($location->getType()) {
                case 'STORE': $inventoryContext->setStoreUuid($location->getUuid()); break;
                case 'SUPPLIER': $inventoryContext->setSupplierUuid($location->getUuid()); break;
                case 'BIN': $inventoryContext->setBinUuid($location->getUuid()); break;
                case 'SOLD': $inventoryContext->setSoldUuid($location->getUuid()); break;
            }
        }
    }

    protected function loadIZettleInventory(InventoryContext $inventoryContext): void
    {
        $inventory = $this->inventoryResource->getInventory(
            $inventoryContext->getIZettleSalesChannel(),
            $inventoryContext->getStoreUuid()
        );
        $inventoryContext->setIZettleInventory($inventory);
    }

    protected function loadLocalInventory(InventoryContext $inventoryContext): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $inventoryContext->getIZettleSalesChannel()->getSalesChannelId()));
        $inventory = $this->inventoryRepository->search($criteria, $inventoryContext->getContext())->getEntities();
        if ($inventory instanceof IZettleSalesChannelInventoryCollection) {
            $inventoryContext->setLocalInventory($inventory);
        }
    }
}
