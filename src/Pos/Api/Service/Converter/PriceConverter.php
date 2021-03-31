<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Api\Service\Converter;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Pos\Api\Product\Variant\Price;

class PriceConverter
{
    public function convert(CalculatedPrice $price, CurrencyEntity $currency): Price
    {
        return $this->convertFloat($price->getTotalPrice(), $currency);
    }

    public function convertFloat(float $price, CurrencyEntity $currency): Price
    {
        $newPrice = new Price();

        $precision = 10 ** ($currency->getItemRounding()->getDecimals());

        $newPrice->setAmount((int) ($price * $precision));
        $newPrice->setCurrencyId($currency->getIsoCode());

        return $newPrice;
    }
}
