<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Resource;

use Swag\PayPal\IZettle\Api\Inventory\BulkChanges;
use Swag\PayPal\IZettle\Api\Inventory\Location;
use Swag\PayPal\IZettle\Api\Inventory\Status;
use Swag\PayPal\IZettle\Api\IZettleBaseURL;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

class InventoryResource
{
    /**
     * @var IZettleClientFactory
     */
    private $iZettleClientFactory;

    public function __construct(IZettleClientFactory $iZettleClientFactory)
    {
        $this->iZettleClientFactory = $iZettleClientFactory;
    }

    /**
     * @return Location[]
     */
    public function getLocations(IZettleSalesChannelEntity $salesChannelEntity): array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $apiKey);

        $response = $client->sendGetRequest(IZettleRequestUri::INVENTORY_RESOURCE_LOCATIONS);

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

    public function getInventory(IZettleSalesChannelEntity $salesChannelEntity, string $locationUuid): Status
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $apiKey);

        $url = \sprintf(IZettleRequestUri::INVENTORY_RESOURCE_GET, $locationUuid);

        $response = $client->sendGetRequest($url);

        $inventory = new Status();
        if ($response !== null) {
            $inventory->assign($response);
        }

        return $inventory;
    }

    public function changeInventoryBulk(IZettleSalesChannelEntity $salesChannelEntity, BulkChanges $bulkChanges): ?Status
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $apiKey);

        $response = $client->sendPostRequest(IZettleRequestUri::INVENTORY_RESOURCE_BULK, $bulkChanges);

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
