<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Context;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;

class ProductContext
{
    public const PRODUCT_NEW = 0;
    public const PRODUCT_OUTDATED = 1;
    public const PRODUCT_CURRENT = 2;

    /**
     * @var SalesChannelEntity
     */
    protected $salesChannel;

    /**
     * @var IZettleSalesChannelProductCollection
     */
    protected $iZettleProductCollection;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var array
     */
    protected $productChanges;

    /**
     * @var array
     */
    protected $productRemovals;

    public function __construct(
        SalesChannelEntity $salesChannel,
        IZettleSalesChannelProductCollection $iZettleProductCollection,
        Context $context
    ) {
        $this->salesChannel = $salesChannel;
        $this->iZettleProductCollection = $iZettleProductCollection;
        $this->context = $context;
        $this->productChanges = [];
        $this->productRemovals = [];
    }

    public function changeProduct(ProductEntity $shopwareProduct, ?Product $iZettleProduct = null): void
    {
        $this->productChanges[] = [
            'salesChannelId' => $this->salesChannel->getId(),
            'productId' => $shopwareProduct->getId(),
            'productVersionId' => $shopwareProduct->getVersionId(),
            'checksum' => $iZettleProduct !== null ? $iZettleProduct->generateChecksum() : '0',
        ];
    }

    public function removeProduct(ProductEntity $shopwareProduct): void
    {
        $this->productRemovals[] = [
            'salesChannelId' => $this->salesChannel->getId(),
            'productId' => $shopwareProduct->getId(),
            'productVersionId' => $shopwareProduct->getVersionId(),
        ];
    }

    public function removeProductReference(IZettleSalesChannelProductEntity $productReference): void
    {
        $this->productRemovals[] = [
            'salesChannelId' => $productReference->getSalesChannelId(),
            'productId' => $productReference->getProductId(),
            'productVersionId' => $productReference->getProductVersionId(),
        ];
    }

    public function checkForUpdate(ProductEntity $shopwareProduct, Product $iZettleProduct): int
    {
        $checksums = $this->iZettleProductCollection->filter(
            static function (IZettleSalesChannelProductEntity $entity) use ($shopwareProduct) {
                return $entity->getProductId() === $shopwareProduct->getId()
                    && $entity->getProductVersionId() === $shopwareProduct->getVersionId();
            }
        );

        $previousChecksum = $checksums->first();

        if ($previousChecksum === null) {
            return self::PRODUCT_NEW;
        }

        return $previousChecksum->getChecksum() === $iZettleProduct->generateChecksum() ? self::PRODUCT_CURRENT : self::PRODUCT_OUTDATED;
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getIZettleSalesChannel(): IZettleSalesChannelEntity
    {
        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $this->salesChannel->getExtension('paypalIZettleSalesChannel');

        return $iZettleSalesChannel;
    }

    public function getIZettleProductCollection(): IZettleSalesChannelProductCollection
    {
        return $this->iZettleProductCollection;
    }

    public function setIZettleProductCollection(IZettleSalesChannelProductCollection $iZettleProductCollection): void
    {
        $this->iZettleProductCollection = $iZettleProductCollection;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getProductChanges(): array
    {
        return $this->productChanges;
    }

    public function getProductRemovals(): array
    {
        return $this->productRemovals;
    }

    public function resetChanges(): void
    {
        $this->productRemovals = [];
        $this->productChanges = [];
    }
}
