<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Product\Util;

use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Product;

#[Package('checkout')]
class ProductGrouping
{
    private SalesChannelProductEntity $identifyingEntity;

    /**
     * @var SalesChannelProductEntity[]
     */
    private array $variantEntities = [];

    private Product $product;

    public function __construct(SalesChannelProductEntity $product)
    {
        $this->identifyingEntity = $product;
        if ($product->getParentId() !== null) {
            $this->variantEntities[] = $product;
        }
    }

    public function addProduct(SalesChannelProductEntity $product): void
    {
        if ($product->getParentId() === null) {
            $this->identifyingEntity = $product;

            return;
        }

        $this->variantEntities[] = $product;
    }

    public function getVariantEntities(): array
    {
        return $this->variantEntities;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): void
    {
        $this->product = $product;
    }

    public function getIdentifyingEntity(): SalesChannelProductEntity
    {
        return $this->identifyingEntity;
    }

    public function getIdentifyingId(): string
    {
        return $this->identifyingEntity->getParentId() ?? $this->identifyingEntity->getId();
    }
}
