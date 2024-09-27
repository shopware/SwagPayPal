<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Context;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\Api\Product;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductEntity;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class ProductContext
{
    public const PRODUCT_NEW = 0;
    public const PRODUCT_OUTDATED = 1;
    public const PRODUCT_CURRENT = 2;

    protected SalesChannelEntity $salesChannel;

    protected PosSalesChannelProductCollection $posProductCollection;

    protected PosSalesChannelMediaCollection $posMediaCollection;

    protected Context $context;

    protected array $productChanges;

    protected array $productRemovals;

    protected array $mediaRequests;

    public function __construct(
        SalesChannelEntity $salesChannel,
        PosSalesChannelProductCollection $posProductCollection,
        PosSalesChannelMediaCollection $posMediaCollection,
        Context $context,
    ) {
        $this->salesChannel = $salesChannel;
        $this->posProductCollection = $posProductCollection;
        $this->posMediaCollection = $posMediaCollection;
        $this->context = $context;
        $this->productChanges = [];
        $this->productRemovals = [];
        $this->mediaRequests = [];
    }

    public function changeProduct(ProductEntity $shopwareProduct, ?Product $posProduct = null): void
    {
        $this->productChanges[] = [
            'salesChannelId' => $this->salesChannel->getId(),
            'productId' => $shopwareProduct->getId(),
            'productVersionId' => $shopwareProduct->getVersionId(),
            'checksum' => $posProduct !== null ? $posProduct->generateChecksum() : '0',
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

    public function removeProductReference(PosSalesChannelProductEntity $productReference): void
    {
        $this->productRemovals[] = [
            'salesChannelId' => $productReference->getSalesChannelId(),
            'productId' => $productReference->getProductId(),
            'productVersionId' => $productReference->getProductVersionId(),
        ];
    }

    public function checkForUpdate(ProductEntity $shopwareProduct, Product $posProduct): int
    {
        $checksums = $this->posProductCollection->filter(
            static function (PosSalesChannelProductEntity $entity) use ($shopwareProduct) {
                return $entity->getProductId() === $shopwareProduct->getId()
                    && $entity->getProductVersionId() === $shopwareProduct->getVersionId();
            }
        );

        $previousChecksum = $checksums->first();

        if ($previousChecksum === null) {
            return self::PRODUCT_NEW;
        }

        return $previousChecksum->getChecksum() === $posProduct->generateChecksum() ? self::PRODUCT_CURRENT : self::PRODUCT_OUTDATED;
    }

    public function checkForMediaUrl(MediaEntity $mediaEntity): ?string
    {
        if (!$mediaEntity->hasFile()) {
            return null;
        }

        $media = $this->posMediaCollection->filter(
            static function (PosSalesChannelMediaEntity $entity) use ($mediaEntity) {
                return $entity->getMediaId() === $mediaEntity->getId();
            }
        );

        $existingMedia = $media->first();

        if ($existingMedia !== null) {
            return $existingMedia->getUrl();
        }

        $this->mediaRequests[] = [
            'salesChannelId' => $this->salesChannel->getId(),
            'mediaId' => $mediaEntity->getId(),
        ];

        return null;
    }

    public function getSalesChannel(): SalesChannelEntity
    {
        return $this->salesChannel;
    }

    public function getPosSalesChannel(): PosSalesChannelEntity
    {
        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $this->salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        return $posSalesChannel;
    }

    public function getPosProductCollection(): PosSalesChannelProductCollection
    {
        return $this->posProductCollection;
    }

    public function setPosProductCollection(PosSalesChannelProductCollection $posProductCollection): void
    {
        $this->posProductCollection = $posProductCollection;
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

    public function getMediaRequests(): array
    {
        return $this->mediaRequests;
    }

    public function resetChanges(): void
    {
        $this->productRemovals = [];
        $this->productChanges = [];
        $this->mediaRequests = [];
    }
}
