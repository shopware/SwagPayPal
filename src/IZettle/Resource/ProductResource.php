<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Resource;

use Swag\PayPal\IZettle\Api\IZettleBaseURL;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\Client\IZettleClientFactory;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

class ProductResource
{
    /**
     * @var IZettleClientFactory
     */
    private $iZettleClientFactory;

    public function __construct(IZettleClientFactory $iZettleClientFactory)
    {
        $this->iZettleClientFactory = $iZettleClientFactory;
    }

    public function createProduct(IZettleSalesChannelEntity $salesChannelEntity, Product $product): ?array
    {
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $username, $password);

        return $client->sendPostRequest(IZettleRequestUri::PRODUCT_RESOURCE, $product);
    }

    public function updateProduct(IZettleSalesChannelEntity $salesChannelEntity, Product $product): ?array
    {
        // TODO: Refactor to API key auth
        $username = $salesChannelEntity->getUsername();
        $password = $salesChannelEntity->getPassword();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $username, $password);

        return $client->sendPutRequest(IZettleRequestUri::PRODUCT_RESOURCE_V2 . $product->getUuid(), $product);
    }
}
