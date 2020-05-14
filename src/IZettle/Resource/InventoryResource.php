<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Resource;

use Swag\PayPal\IZettle\Api\Inventory\Changes;
use Swag\PayPal\IZettle\Api\Inventory\Location;
use Swag\PayPal\IZettle\Api\Inventory\StartTracking;
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
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $username, $password);

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
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $username, $password);

        $url = \sprintf(IZettleRequestUri::INVENTORY_RESOURCE_GET, $locationUuid);

        $response = $client->sendGetRequest($url);

        $inventory = new Status();
        if ($response !== null) {
            $inventory->assign($response);
        }

        return $inventory;
    }

    public function changeInventory(IZettleSalesChannelEntity $salesChannelEntity, Changes $changes): ?Status
    {
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $username, $password);

        $response = $client->sendPutRequest(IZettleRequestUri::INVENTORY_RESOURCE, $changes);

        if ($changes->getReturnBalanceForLocationUuid() === null) {
            return null;
        }

        $inventory = new Status();
        if ($response !== null) {
            $inventory->assign($response);
        }

        return $inventory;
    }

    public function startTracking(IZettleSalesChannelEntity $salesChannelEntity, string $productUuid): ?Status
    {
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::INVENTORY, $username, $password);

        $startTracking = new StartTracking();
        $startTracking->setProductUuid($productUuid);

        $response = $client->sendPostRequest(IZettleRequestUri::INVENTORY_RESOURCE, $startTracking);

        if ($response === null) {
            return null;
        }

        $inventory = new Status();
        $inventory->assign($response);

        return $inventory;
    }
}
