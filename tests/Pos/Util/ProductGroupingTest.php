<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Util;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\Sync\Product\Util\ProductGrouping;
use Swag\PayPal\Pos\Sync\Product\Util\ProductGroupingCollection;

/**
 * @internal
 */
class ProductGroupingTest extends TestCase
{
    public function testAddProduct(): void
    {
        $parentProduct = $this->createProduct();
        $grouping = new ProductGrouping($parentProduct);
        static::assertSame($parentProduct, $grouping->getIdentifyingEntity());
        static::assertSame($parentProduct->getId(), $grouping->getIdentifyingId());
        static::assertEmpty($grouping->getVariantEntities());

        $productOne = $this->createProduct($parentProduct->getId());
        $grouping = new ProductGrouping($productOne);
        static::assertSame($productOne, $grouping->getIdentifyingEntity());
        static::assertSame($productOne->getParentId(), $grouping->getIdentifyingId());
        static::assertContains($productOne, $grouping->getVariantEntities());

        $productTwo = $this->createProduct($parentProduct->getId());
        $grouping->addProduct($productTwo);
        static::assertSame($productOne, $grouping->getIdentifyingEntity());
        static::assertContains($productTwo, $grouping->getVariantEntities());

        $grouping->addProduct($parentProduct);
        static::assertSame($parentProduct, $grouping->getIdentifyingEntity());
        static::assertNotContains($parentProduct, $grouping->getVariantEntities());
    }

    public function testCollection(): void
    {
        $parentProduct = $this->createProduct();
        $childProductOne = $this->createProduct($parentProduct->getId());
        $childProductTwo = $this->createProduct($parentProduct->getId());
        $singleParentId = Uuid::randomHex();
        $childProductWithoutParent = $this->createProduct($singleParentId);

        $productCollection = new ProductCollection();
        $productCollection->add($parentProduct);
        $productCollection->add($childProductOne);
        $productCollection->add($childProductTwo);
        $productCollection->add($childProductWithoutParent);

        $productGroupingCollection = new ProductGroupingCollection();
        $productGroupingCollection->addProducts($productCollection);

        static::assertEqualsCanonicalizing(
            [$singleParentId, $parentProduct->getId()],
            $productGroupingCollection->getKeys()
        );
    }

    protected function createProduct(?string $parentId = null): SalesChannelProductEntity
    {
        $product = new SalesChannelProductEntity();
        $product->setId(Uuid::randomHex());
        if ($parentId !== null) {
            $product->setParentId($parentId);
        }

        return $product;
    }
}
