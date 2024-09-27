<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Product;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Context\ProductContext;

#[Package('checkout')]
class DeletedUpdater
{
    private ProductResource $productResource;

    private LoggerInterface $logger;

    private UuidConverter $uuidConverter;

    /**
     * @internal
     */
    public function __construct(
        ProductResource $productResource,
        LoggerInterface $logger,
        UuidConverter $uuidConverter,
    ) {
        $this->productResource = $productResource;
        $this->logger = $logger;
        $this->uuidConverter = $uuidConverter;
    }

    /**
     * @param string[] $productIds
     */
    public function update(array $productIds, ProductContext $productContext): void
    {
        $posProductCollection = $productContext->getPosProductCollection();

        $deletions = [];

        foreach ($posProductCollection->getElements() as $posProductEntity) {
            if (\in_array($posProductEntity->getProductId(), $productIds, true)) {
                continue;
            }

            $deletions[$posProductEntity->getProductId()] = $posProductEntity;
        }

        if (\count($deletions) === 0) {
            return;
        }

        $productUuids = \array_map([$this->uuidConverter, 'convertUuidToV1'], \array_keys($deletions));

        try {
            $this->productResource->deleteProducts($productContext->getPosSalesChannel(), $productUuids);
            $this->logger->info('Deleted products at Zettle: {productIds}', ['productIds' => \implode(', ', \array_keys($deletions))]);
        } catch (PosApiException $posApiException) {
            $this->logger->error('Product deletion error: ' . $posApiException);
        }

        foreach ($deletions as $deletion) {
            $productContext->removeProductReference($deletion);
        }
    }
}
