<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Product;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class ProductCountResponse extends IZettleStruct
{
    /**
     * @var int
     */
    protected $productCount;

    public function getProductCount(): int
    {
        return $this->productCount;
    }

    protected function setProductCount(int $productCount): void
    {
        $this->productCount = $productCount;
    }
}
