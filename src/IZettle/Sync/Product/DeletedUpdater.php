<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Converter\UuidConverter;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class DeletedUpdater
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

    public function update(ProductGroupingCollection $productGroupings, ProductContext $productContext): void
    {
        $iZettleProductCollection = $productContext->getIZettleProductCollection();

        foreach ($iZettleProductCollection->getElements() as $salesChannelProductEntity) {
            $productGrouping = $productGroupings->get($salesChannelProductEntity->getProductId());

            if ($productGrouping !== null) {
                continue;
            }

            $productUuid = $this->uuidConverter->convertUuidToV1($salesChannelProductEntity->getProductId());

            try {
                $this->productResource->deleteProduct($productContext->getIZettleSalesChannel(), $productUuid);
            } catch (IZettleApiException $iZettleApiException) {
                if ($iZettleApiException->getApiError()->getErrorType() !== IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND) {
                    throw $iZettleApiException;
                }
            }

            $productContext->removeProductReference($salesChannelProductEntity);
        }
    }
}
