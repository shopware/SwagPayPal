<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Product;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Error\PosApiError;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Sync\Context\ProductContext;
use Swag\PayPal\Pos\Sync\Product\Util\ProductGroupingCollection;

#[Package('checkout')]
class OutdatedUpdater
{
    private ProductResource $productResource;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        ProductResource $productResource,
        LoggerInterface $logger,
    ) {
        $this->productResource = $productResource;
        $this->logger = $logger;
    }

    public function update(ProductGroupingCollection $productGroupings, ProductContext $productContext): void
    {
        foreach ($productGroupings as $productGrouping) {
            $product = $productGrouping->getProduct();
            $shopwareProduct = $productGrouping->getIdentifyingEntity();

            $updateStatus = $productContext->checkForUpdate($shopwareProduct, $product);

            if ($updateStatus === ProductContext::PRODUCT_OUTDATED) {
                try {
                    $this->productResource->updateProduct($productContext->getPosSalesChannel(), $product);
                    $productContext->changeProduct($shopwareProduct, $product);
                    $this->logger->info('Product updated', ['product' => $shopwareProduct]);
                } catch (PosApiException $posApiException) {
                    if ($posApiException->getApiError()->getErrorType() === PosApiError::ERROR_TYPE_ENTITY_NOT_FOUND) {
                        $productContext->removeProduct($shopwareProduct);
                        $this->logger->notice('The product was marked as synced, but could not be found at Zettle. It will be recreated with the next sync.', ['product' => $shopwareProduct]);
                    } else {
                        $this->logger->error('Product update error: ' . $posApiException, ['product' => $shopwareProduct]);
                    }
                }
            }
        }
    }
}
