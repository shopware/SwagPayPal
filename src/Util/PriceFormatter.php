<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

class PriceFormatter
{
    private const DEFAULT_DECIMALS = 2;

    public function formatPrice(float $price): string
    {
        return \number_format($this->roundPrice($price), self::DEFAULT_DECIMALS, '.', '');
    }

    public function roundPrice(float $price): float
    {
        return \round($price, self::DEFAULT_DECIMALS);
    }
}
