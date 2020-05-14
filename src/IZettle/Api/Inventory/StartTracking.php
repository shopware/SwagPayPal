<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Inventory;

use Swag\PayPal\IZettle\Api\Common\IZettleStruct;

class StartTracking extends IZettleStruct
{
    /**
     * @var string
     */
    protected $productUuid;

    public function setProductUuid(string $productUuid): void
    {
        $this->productUuid = $productUuid;
    }
}
