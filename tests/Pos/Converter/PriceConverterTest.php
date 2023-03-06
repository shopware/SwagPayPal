<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Swag\PayPal\Pos\Api\Service\Converter\PriceConverter;

/**
 * @internal
 */
class PriceConverterTest extends TestCase
{
    public function dataProviderPriceConversion(): array
    {
        return [
            [100.02, 10002, 'EUR'],
            [0.0, 0, 'USD'],
            [-51.97, -5197, 'XXX'],
        ];
    }

    /**
     * @dataProvider dataProviderPriceConversion
     */
    public function testConvert(float $floatValue, int $intValue, string $currencyCode): void
    {
        $shopwarePrice = new CalculatedPrice($floatValue, $floatValue, new CalculatedTaxCollection(), new TaxRuleCollection());
        $currency = new CurrencyEntity();
        $currency->setIsoCode($currencyCode);
        $price = $this->createPriceConverter()->convert($shopwarePrice, $currency);
        static::assertSame($intValue, $price->getAmount());
        static::assertSame($currency->getIsoCode(), $price->getCurrencyId());
    }

    /**
     * @dataProvider dataProviderPriceConversion
     */
    public function testConvertFloat(float $floatValue, int $intValue, string $currencyCode): void
    {
        $currency = new CurrencyEntity();
        $currency->setIsoCode($currencyCode);
        $price = $this->createPriceConverter()->convertFloat($floatValue, $currency);
        static::assertSame($intValue, $price->getAmount());
        static::assertSame($currency->getIsoCode(), $price->getCurrencyId());
    }

    private function createPriceConverter(): PriceConverter
    {
        return new PriceConverter();
    }
}
