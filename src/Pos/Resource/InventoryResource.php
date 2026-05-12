<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Inventory\BulkChanges;
use Swag\PayPal\Pos\Api\Inventory\Location;
use Swag\PayPal\Pos\Api\Inventory\Status;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;

#[Package('checkout')]
class InventoryResource
{
    private PosClientFactory $posClientFactory;

    /**
     * @internal
     */
    public function __construct(PosClientFactory $posClientFactory)
    {
        $this->posClientFactory = $posClientFactory;
    }

    /**
     * @return Location[]
     */
    public function getLocations(PosSalesChannelEntity $salesChannelEntity): array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::INVENTORY, $apiKey);

        $response = $client->sendGetRequest(PosRequestUri::INVENTORY_RESOURCE_LOCATIONS);

        if ($response === null) {
            return [];
        }

        $locations = [];
        foreach ($response as $locationData) {
            $location = new Location();
            $location->assign($locationData);
            $locations[] = $location;
        }

        return $locations;
    }

    public function getInventory(PosSalesChannelEntity $salesChannelEntity, string $locationUuid): Status
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::INVENTORY, $apiKey);

        $url = \sprintf(PosRequestUri::INVENTORY_RESOURCE_GET, $locationUuid);

        $response = $client->sendGetRequest($url);

        $inventory = new Status();
        if ($response !== null) {
            $inventory->assign($response);
        }

        return $inventory;
    }

    public function changeInventoryBulk(PosSalesChannelEntity $salesChannelEntity, BulkChanges $bulkChanges): ?Status
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::INVENTORY, $apiKey);

        $response = $client->sendPostRequest(PosRequestUri::INVENTORY_RESOURCE_BULK, $bulkChanges);

        if ($bulkChanges->getReturnBalanceForLocationUuid() === null) {
            return null;
        }

        $inventory = new Status();
        if ($response !== null) {
            $inventory->assign($response);
        }

        return $inventory;
    }
}
