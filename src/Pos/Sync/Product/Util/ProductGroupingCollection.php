<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Product\Util;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Collection;

/**
 * @extends Collection<ProductGrouping>
 */
#[Package('checkout')]
class ProductGroupingCollection extends Collection
{
    public function addProducts(ProductCollection $products): void
    {
        foreach ($products as $product) {
            if (!($product instanceof SalesChannelProductEntity)) {
                continue;
            }

            $grouping = $this->findGrouping($product);
            if ($grouping === null) {
                $grouping = new ProductGrouping($product);
                $this->set($grouping->getIdentifyingId(), $grouping);
            } else {
                $grouping->addProduct($product);
            }
        }
    }

    protected function getExpectedClass(): string
    {
        return ProductGrouping::class;
    }

    private function findGrouping(SalesChannelProductEntity $product): ?ProductGrouping
    {
        if ($product->getParentId() === null) {
            return $this->get($product->getId());
        }

        return $this->get($product->getParentId());
    }
}
