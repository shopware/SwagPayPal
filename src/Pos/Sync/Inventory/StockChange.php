<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Inventory;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

#[Package('checkout')]
class StockChange extends Struct
{
    public const STOCK_CHANGE_EXTENSION = 'stockChange';

    private int $stockChange;

    public function __construct(int $stockChange)
    {
        $this->stockChange = $stockChange;
    }

    public function getStockChange(): int
    {
        return $this->stockChange;
    }
}
