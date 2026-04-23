<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class ProductStockAccessor
{
    public static function get(ProductEntity $product): int
    {
        if (Feature::isActive('v6.8.0.0')) {
            return $product->getStock();
        }

        /** @phpstan-ignore method.deprecated */
        return (int) $product->getAvailableStock();
    }

    public static function set(ProductEntity $product, int $stock): void
    {
        if (Feature::isActive('v6.8.0.0')) {
            $product->setStock($stock);

            return;
        }

        /** @phpstan-ignore method.deprecated */
        $product->setAvailableStock($stock);
    }
}
