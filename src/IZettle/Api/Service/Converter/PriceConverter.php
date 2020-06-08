<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Api\Service\Converter;

use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price as ShopwarePrice;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\IZettle\Api\Product\Price as ConvertedPrice;

class PriceConverter
{
    public function convert(ShopwarePrice $price, CurrencyEntity $currency): ConvertedPrice
    {
        $newPrice = new ConvertedPrice();

        $floatPrice = $price->getNet();
        $precision = 10 ** ($currency->getDecimalPrecision());

        $newPrice->setAmount((int) ($floatPrice * $precision));
        $newPrice->setCurrencyId($currency->getIsoCode());

        return $newPrice;
    }
}
