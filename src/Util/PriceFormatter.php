<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class PriceFormatter
{
    private const DEFAULT_DECIMALS = 2;

    private const OTHER_DECIMALS = [
        'HUF' => 0,
        'JPY' => 0,
        'TWD' => 0,
    ];

    /**
     * @deprecated tag:v11.0.0 reason:parameter-type-change - `$countryCode` will be renamed to `$currencyIso`
     */
    public function formatPrice(float $price, ?string $countryCode = null): string
    {
        $decimals = $this->getDecimals($countryCode);

        return \number_format($this->roundPrice($price, $countryCode), $decimals, '.', '');
    }

    /**
     * @deprecated tag:v11.0.0 reason:parameter-type-change - `$countryCode` will be renamed to `$currencyIso`
     */
    public function roundPrice(float $price, ?string $countryCode = null): float
    {
        $decimals = $this->getDecimals($countryCode);

        return \round($price, $decimals);
    }

    private function getDecimals(?string $currencyIso): int
    {
        if ($currencyIso === null) {
            return self::DEFAULT_DECIMALS;
        }

        return self::OTHER_DECIMALS[$currencyIso] ?? self::DEFAULT_DECIMALS;
    }
}
