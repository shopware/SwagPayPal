<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Product;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Common\PosStruct;

#[Package('checkout')]
class ProductCountResponse extends PosStruct
{
    protected int $productCount;

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    protected function setProductCount(int $productCount): void
    {
        $this->productCount = $productCount;
    }
}
