<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
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

        $deletions = [];

        foreach ($iZettleProductCollection->getElements() as $iZettleProductEntity) {
            if (\in_array($iZettleProductEntity->getProductId(), $productIds, true)) {
                continue;
            }

            $deletions[$iZettleProductEntity->getProductId()] = $iZettleProductEntity;
        }

        if (\count($deletions) === 0) {
            return;
        }

        $productUuids = \array_map([$this->uuidConverter, 'convertUuidToV1'], \array_keys($deletions));

        try {
            $this->productResource->deleteProducts($productContext->getIZettleSalesChannel(), $productUuids);
            $this->logger->info('Deleted products at iZettle: {productIds}', ['productIds' => \implode(', ', \array_keys($deletions))]);
        } catch (IZettleApiException $iZettleApiException) {
            $this->logger->error('Product deletion error: ' . $iZettleApiException);
        }

        foreach ($deletions as $deletion) {
            $productContext->removeProductReference($deletion);
        }
    }
}
