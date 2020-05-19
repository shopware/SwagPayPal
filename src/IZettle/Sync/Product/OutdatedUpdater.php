<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class OutdatedUpdater
{
    /**
     * @var ProductResource
     */
    private $productResource;

    public function __construct(ProductResource $productResource)
    {
        $this->productResource = $productResource;
    }

    public function update(ProductGroupingCollection $productGroupings, ProductContext $productContext): void
    {
        foreach ($productGroupings as $productGrouping) {
            $product = $productGrouping->getProduct();
            $shopwareProduct = $productGrouping->getIdentifyingEntity();

            $updateStatus = $productContext->checkForUpdate($shopwareProduct, $product);

            if ($updateStatus === ProductContext::PRODUCT_OUTDATED) {
                try {
                    $this->productResource->updateProduct($productContext->getIZettleSalesChannel(), $product);
                    $productContext->changeProduct($shopwareProduct, $product);
                } catch (IZettleApiException $iZettleApiException) {
                    if ($iZettleApiException->getApiError()->getErrorType() === IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND) {
                        $productContext->removeProduct($shopwareProduct);
                    } else {
                        throw $iZettleApiException;
                    }
                }
            }
        }
    }
}
