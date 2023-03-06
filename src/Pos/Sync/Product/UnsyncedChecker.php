<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Product;

use Psr\Log\LoggerInterface;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Context\ProductContext;

class UnsyncedChecker
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
        UuidConverter $uuidConverter
    ) {
        $this->productResource = $productResource;
        $this->logger = $logger;
        $this->uuidConverter = $uuidConverter;
    }

    /**
     * @param string[] $productIds
     */
    public function checkForUnsynced(array $productIds, ProductContext $productContext): void
    {
        if ($productContext->getPosSalesChannel()->getReplace() === PosSalesChannelEntity::REPLACE_OFF) {
            return;
        }

        $existingPosProducts = $this->productResource->getProducts($productContext->getPosSalesChannel());
        $deletions = [];

        foreach ($existingPosProducts as $posProduct) {
            $uuid = $this->uuidConverter->convertUuidToV4($posProduct->getUuid());

            if (\in_array($uuid, $productIds, true)) {
                continue;
            }

            if ($productContext->getPosProductCollection()->hasProduct($uuid)) {
                continue;
            }

            $deletions[] = $posProduct->getUuid();
        }

        if (\count($deletions) === 0) {
            return;
        }

        try {
            $this->productResource->deleteProducts($productContext->getPosSalesChannel(), $deletions);
            $this->logger->info('Removed unsynced products at Zettle: {productIds}', ['productIds' => \implode(', ', $deletions)]);
        } catch (PosApiException $posApiException) {
            $this->logger->warning('Unsynced product deletion error: ' . $posApiException);
        }
    }
}
