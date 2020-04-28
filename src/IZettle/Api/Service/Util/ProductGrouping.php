<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Util;

use Shopware\Core\Content\Product\ProductEntity;
use Swag\PayPal\IZettle\Api\Product;

class ProductGrouping
{
    /**
     * @var ProductEntity
     */
    private $identifyingEntity;

    /**
     * @var ProductEntity[]
     */
    private $variantEntities = [];

    /**
     * @var Product
     */
    private $product;

    public function __construct(ProductEntity $product)
    {
        $this->identifyingEntity = $product;
        if ($product->getParentId() !== null) {
            $this->variantEntities[] = $product;
        }
    }

    public function addProduct(ProductEntity $product): void
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

    public function getIdentifyingEntity(): ProductEntity
    {
        return $this->identifyingEntity;
    }

    public function getIdentifyingId(): string
    {
        return $this->identifyingEntity->getParentId() ?? $this->identifyingEntity->getId();
    }
}
