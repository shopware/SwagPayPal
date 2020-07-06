<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

class PriceFormatter
{
    public function formatPrice(float $price): string
    {
        return \number_format($this->roundPrice($price), 2);
    }

    public function roundPrice(float $price): float
    {
        return \round($price, 2);
    }
}
