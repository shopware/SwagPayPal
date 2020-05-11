<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\Service\ProductConverter;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Resource\ProductResource;

class ProductSyncer
{
    /**
     * @var ProductSelection
     */
    private $productSelection;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var ProductConverter
     */
    private $productConverter;

    /**
     * @var ChecksumResource
     */
    private $checksumResource;

    public function __construct(
        ProductSelection $productSelection,
        ProductResource $productResource,
        ProductConverter $productConverter,
        ChecksumResource $checksumResource
    ) {
        $this->productSelection = $productSelection;
        $this->productResource = $productResource;
        $this->productConverter = $productConverter;
        $this->checksumResource = $checksumResource;
    }

    public function syncProducts(SalesChannelEntity $salesChannel, Context $context): void
    {
        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension('paypalIZettleSalesChannel');
        $currency = $iZettleSalesChannel->isSyncPrices() ? $salesChannel->getCurrency() : null;

        $shopwareProducts = $this->productSelection->getProducts($iZettleSalesChannel, $context, true);
        $productGroupings = $this->productConverter->convertShopwareProducts($shopwareProducts, $currency);

        $this->checksumResource->begin($salesChannel->getId(), $context);

        foreach ($productGroupings as $productGrouping) {
            $product = $productGrouping->getProduct();
            $shopwareProduct = $productGrouping->getIdentifyingEntity();

            $updateStatus = $this->checksumResource->checkForUpdate($shopwareProduct, $product);

            if ($updateStatus === ChecksumResource::PRODUCT_NEW) {
                try {
                    $this->productResource->createProduct($iZettleSalesChannel, $product);
                    $this->checksumResource->addProduct($shopwareProduct, $product, $salesChannel->getId());
                } catch (IZettleApiException $iZettleApiException) {
                    if ($iZettleApiException->getApiError()->getErrorType() === IZettleApiError::ERROR_TYPE_ITEM_ALREADY_EXISTS) {
                        $updateStatus = ChecksumResource::PRODUCT_OUTDATED;
                    } else {
                        throw $iZettleApiException;
                    }
                }
            }

            if ($updateStatus === ChecksumResource::PRODUCT_OUTDATED) {
                try {
                    $this->productResource->updateProduct($iZettleSalesChannel, $product);
                    $this->checksumResource->addProduct($shopwareProduct, $product, $salesChannel->getId());
                } catch (IZettleApiException $iZettleApiException) {
                    if ($iZettleApiException->getApiError()->getErrorType() === IZettleApiError::ERROR_TYPE_ENTITY_NOT_FOUND) {
                        $this->checksumResource->removeProduct($shopwareProduct, $salesChannel->getId());
                    } else {
                        throw $iZettleApiException;
                    }
                }
            }
        }

        $this->checksumResource->commit($context);
    }
}
