<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class UnsyncedChecker
{
    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var UuidConverter
     */
    private $uuidConverter;

    public function __construct(
        ProductResource $productResource,
        UuidConverter $uuidConverter
    ) {
        $this->productResource = $productResource;
        $this->uuidConverter = $uuidConverter;
    }

    public function checkForUnsynced(ProductGroupingCollection $productGroupings, ProductContext $productContext): void
    {
        if (!$productContext->getIZettleSalesChannel()->isReplace()) {
            return;
        }

        $existingIZettleProducts = $this->productResource->getProducts($productContext->getIZettleSalesChannel());
        $deletions = [];

        foreach ($existingIZettleProducts as $iZettleProduct) {
            $uuid = $this->uuidConverter->convertUuidToV4($iZettleProduct->getUuid());

            if (!$uuid) {
                continue;
            }

            if ($productGroupings->has($uuid)) {
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

        $this->productResource->deleteProducts($productContext->getIZettleSalesChannel(), $deletions);
    }
}
