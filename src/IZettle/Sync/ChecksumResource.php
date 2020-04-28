<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\IZettle\Api\Product;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductEntity;

class ChecksumResource
{
    public const PRODUCT_NEW = 0;
    public const PRODUCT_OUTDATED = 1;
    public const PRODUCT_CURRENT = 2;

    /**
     * @var EntityCollection|null
     */
    protected $checksumEntities;

    /**
     * @var array
     */
    protected $updatedProducts;

    /**
     * @var array
     */
    protected $removedProducts;

    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleSalesChannelProductRepository;

    public function __construct(EntityRepositoryInterface $iZettleSalesChannelProductRepository)
    {
        $this->iZettleSalesChannelProductRepository = $iZettleSalesChannelProductRepository;
        $this->updatedProducts = [];
        $this->removedProducts = [];
    }

    public function begin(string $salesChannelId, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $this->checksumEntities = $this->iZettleSalesChannelProductRepository->search($criteria, $context)->getEntities();
    }

    public function addProduct(ProductEntity $shopwareProduct, Product $iZettleProduct, string $salesChannelId): void
    {
        $this->updatedProducts[] = [
            'salesChannelId' => $salesChannelId,
            'productId' => $shopwareProduct->getId(),
            'productVersionId' => $shopwareProduct->getVersionId(),
            'checksum' => $iZettleProduct->generateChecksum(),
        ];
    }

    public function removeProduct(ProductEntity $shopwareProduct, string $salesChannelId): void
    {
        $this->removedProducts[] = [
            'salesChannelId' => $salesChannelId,
            'productId' => $shopwareProduct->getId(),
            'productVersionId' => $shopwareProduct->getVersionId(),
        ];
    }

    public function commit(Context $context): void
    {
        if ($this->updatedProducts) {
            $this->iZettleSalesChannelProductRepository->upsert($this->updatedProducts, $context);
        }

        if ($this->removedProducts) {
            $this->iZettleSalesChannelProductRepository->delete($this->removedProducts, $context);
        }
    }

    public function checkForUpdate(ProductEntity $shopwareProduct, Product $iZettleProduct): int
    {
        if ($this->checksumEntities === null) {
            throw new \RuntimeException('Checksums have not been initialized');
        }

        $checksums = $this->checksumEntities->filter(
            static function (IZettleSalesChannelProductEntity $entity) use ($shopwareProduct) {
                return $entity->getProductId() === $shopwareProduct->getId()
                    && $entity->getProductVersionId() === $shopwareProduct->getVersionId();
            }
        );

        if ($checksums->count() === 0) {
            return self::PRODUCT_NEW;
        }

        $previousChecksum = $checksums->first();

        return $previousChecksum->getChecksum() === $iZettleProduct->generateChecksum() ? self::PRODUCT_CURRENT : self::PRODUCT_OUTDATED;
    }
}
