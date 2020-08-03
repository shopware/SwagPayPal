<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class UnsyncedChecker
{
    /**
     * @var ProductResource
     */
    private $productResource;

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
        if (!$productContext->getIZettleSalesChannel()->isReplace()) {
            return;
        }

        $existingIZettleProducts = $this->productResource->getProducts($productContext->getIZettleSalesChannel());
        $deletions = [];

        foreach ($existingIZettleProducts as $iZettleProduct) {
            $uuid = $this->uuidConverter->convertUuidToV4($iZettleProduct->getUuid());

            if (\in_array($uuid, $productIds, true)) {
                continue;
            }

            if ($productContext->getIZettleProductCollection()->hasProduct($uuid)) {
                continue;
            }

            $deletions[] = $iZettleProduct->getUuid();
        }

        if (\count($deletions) === 0) {
            return;
        }

        try {
            $this->productResource->deleteProducts($productContext->getIZettleSalesChannel(), $deletions);
            $this->logger->info('Unsynced products at iZettle deleted: {productIds}', ['productIds' => $deletions]);
        } catch (IZettleApiException $iZettleApiException) {
            $this->logger->warning('Unsynced product deletion error: ' . $iZettleApiException);
        }
    }
}
