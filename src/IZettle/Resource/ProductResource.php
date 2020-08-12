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
use Swag\PayPal\IZettle\Api\Product\ProductCountResponse;
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

    /**
     * @return Product[]
     */
    public function getProducts(IZettleSalesChannelEntity $salesChannelEntity): array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $apiKey);

        $response = $client->sendGetRequest(IZettleRequestUri::PRODUCT_RESOURCE);

        if ($response === null) {
            return [];
        }

        $products = [];
        foreach ($response as $productData) {
            $product = new Product();
            $product->assign($productData);
            $products[] = $product;
        }

        return $products;
    }

    public function createProduct(IZettleSalesChannelEntity $salesChannelEntity, Product $product): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $apiKey);

        return $client->sendPostRequest(IZettleRequestUri::PRODUCT_RESOURCE, $product);
    }

    public function updateProduct(IZettleSalesChannelEntity $salesChannelEntity, Product $product): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $apiKey);

        return $client->sendPutRequest(IZettleRequestUri::PRODUCT_RESOURCE_V2 . $product->getUuid(), $product);
    }

    public function deleteProduct(IZettleSalesChannelEntity $salesChannelEntity, string $productUuid): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $apiKey);

        return $client->sendDeleteRequest(IZettleRequestUri::PRODUCT_RESOURCE . $productUuid);
    }

    /**
     * @param string[] $productUuids
     */
    public function deleteProducts(IZettleSalesChannelEntity $salesChannelEntity, array $productUuids): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $apiKey);

        foreach ($productUuids as &$productUuid) {
            $productUuid = "uuid=${productUuid}";
        }

        return $client->sendDeleteRequest(IZettleRequestUri::PRODUCT_RESOURCE, \implode('&', $productUuids));
    }

    public function getProductCount(IZettleSalesChannelEntity $salesChannelEntity): ProductCountResponse
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->iZettleClientFactory->createIZettleClient(IZettleBaseURL::PRODUCTS, $apiKey);

        $response = $client->sendGetRequest(IZettleRequestUri::PRODUCT_RESOURCE_COUNT);

        $productCountResponse = new ProductCountResponse();
        if ($response !== null) {
            $productCountResponse->assign($response);
        }

        return $productCountResponse;
    }
}
