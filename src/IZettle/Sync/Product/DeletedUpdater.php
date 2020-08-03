<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class DeletedUpdater
{
    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    public function __construct(
        ProductResource $productResource,
        EntityRepositoryInterface $productRepository,
        LoggerInterface $logger,
        UuidConverter $uuidConverter
    ) {
        $this->productResource = $productResource;
        $this->productRepository = $productRepository;
        $this->logger = $logger;
        $this->uuidConverter = $uuidConverter;
    }

    /**
     * @param string[] $productIds
     */
    public function update(array $productIds, ProductContext $productContext): void
    {
        $iZettleProductCollection = $productContext->getIZettleProductCollection();

        foreach ($iZettleProductCollection->getElements() as $salesChannelProductEntity) {
            if (\in_array($salesChannelProductEntity->getProductId(), $productIds, true)) {
                continue;
            }

            $productUuid = $this->uuidConverter->convertUuidToV1($salesChannelProductEntity->getProductId());

            /** @var ProductEntity|null $productEntity */
            $productEntity = $this->productRepository->search(
                new Criteria([$salesChannelProductEntity->getProductId()]),
                $productContext->getContext()
            )->first();

            try {
                $this->productResource->deleteProduct($productContext->getIZettleSalesChannel(), $productUuid);
                $productContext->removeProductReference($salesChannelProductEntity);
                $this->logger->info('Product deleted: {productId}', ['product' => $productEntity, 'productId' => $productUuid]);
            } catch (IZettleApiException $iZettleApiException) {
                if ($iZettleApiException->getApiError()->getErrorType() === IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND) {
                    $productContext->removeProductReference($salesChannelProductEntity);
                    $this->logger->notice('Not found product to delete at iZettle: {productId}', ['product' => $productEntity, 'productId' => $productUuid]);
                } else {
                    $this->logger->error('Product deletion error: ' . $iZettleApiException);
                }
            }
        }
    }
}
