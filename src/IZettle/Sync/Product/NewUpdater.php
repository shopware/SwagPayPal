<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Product;

use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\Util\ProductGroupingCollection;
use Swag\PayPal\IZettle\Resource\ProductResource;
use Swag\PayPal\IZettle\Sync\Context\ProductContext;

class NewUpdater
{
    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ProductResource $productResource, LoggerInterface $logger)
    {
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    public function update(ProductGroupingCollection $productGroupings, ProductContext $productContext): void
    {
        foreach ($productGroupings as $productGrouping) {
            $product = $productGrouping->getProduct();
            $shopwareProduct = $productGrouping->getIdentifyingEntity();

            $updateStatus = $productContext->checkForUpdate($shopwareProduct, $product);

            if ($updateStatus === ProductContext::PRODUCT_NEW) {
                try {
                    $this->productResource->createProduct($productContext->getIZettleSalesChannel(), $product);
                    $productContext->changeProduct($shopwareProduct, $product);
                    $this->logger->info('Product created', ['product' => $shopwareProduct]);
                } catch (IZettleApiException $iZettleApiException) {
                    if ($iZettleApiException->getApiError()->getErrorType() === IZettleApiError::ERROR_TYPE_ITEM_ALREADY_EXISTS) {
                        $productContext->changeProduct($shopwareProduct);
                        $this->logger->notice('The product was not marked as synced, but was found at iZettle. Overwriting.', ['product' => $shopwareProduct]);
                    } else {
                        $this->logger->error('Product creation error: ' . $iZettleApiException, ['product' => $shopwareProduct]);
                    }
                }
            }
        }
    }
}
