<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\Api\Product\ProductCountResponse;
use Swag\PayPal\Pos\Client\PosClientFactory;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;

#[Package('checkout')]
class ProductResource
{
    public const DELETION_CHUNK_SIZE = 100;

    private PosClientFactory $posClientFactory;

    /**
     * @internal
     */
    public function __construct(PosClientFactory $posClientFactory)
    {
        $this->posClientFactory = $posClientFactory;
    }

    /**
     * @return Product[]
     */
    public function getProducts(PosSalesChannelEntity $salesChannelEntity): array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PRODUCTS, $apiKey);

        $response = $client->sendGetRequest(PosRequestUri::PRODUCT_RESOURCE);

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

    public function createProduct(PosSalesChannelEntity $salesChannelEntity, Product $product): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PRODUCTS, $apiKey);

        return $client->sendPostRequest(PosRequestUri::PRODUCT_RESOURCE, $product);
    }

    public function updateProduct(PosSalesChannelEntity $salesChannelEntity, Product $product): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PRODUCTS, $apiKey);

        return $client->sendPutRequest(PosRequestUri::PRODUCT_RESOURCE_V2 . $product->getUuid(), $product);
    }

    /**
     * @param string[] $productUuids
     */
    public function deleteProducts(PosSalesChannelEntity $salesChannelEntity, array $productUuids): ?array
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PRODUCTS, $apiKey);

        // limited by GET request length
        $offset = 0;
        while ($offset < \count($productUuids)) {
            $deletionChunk = \array_splice($productUuids, $offset, self::DELETION_CHUNK_SIZE);
            $deletionChunk = \array_map(static function (string $productUuid) {
                return \sprintf('uuid=%s', $productUuid);
            }, $deletionChunk);

            $client->sendDeleteRequest(PosRequestUri::PRODUCT_RESOURCE, \implode('&', $deletionChunk));
            $offset += self::DELETION_CHUNK_SIZE;
        }

        return null;
    }

    public function getProductCount(PosSalesChannelEntity $salesChannelEntity): ProductCountResponse
    {
        $apiKey = $salesChannelEntity->getApiKey();
        $client = $this->posClientFactory->getPosClient(PosBaseURL::PRODUCTS, $apiKey);

        $response = $client->sendGetRequest(PosRequestUri::PRODUCT_RESOURCE_COUNT);

        $productCountResponse = new ProductCountResponse();
        if ($response !== null) {
            $productCountResponse->assign($response);
        }

        return $productCountResponse;
    }
}
