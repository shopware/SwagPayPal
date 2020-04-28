<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Converter;

use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\IZettle\Api\Product\Price;

class PriceConverter
{
    public function convert(\Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price $price, CurrencyEntity $currency): Price
    {
        $newPrice = new Price();

        $floatPrice = $price->getNet();
        $precision = 10 ** ($currency->getDecimalPrecision());

        $newPrice->setAmount((int) ($floatPrice * $precision));
        $newPrice->setCurrencyId($currency->getIsoCode());

        return $newPrice;
    }
}
