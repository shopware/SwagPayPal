<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
#[Package('checkout')]
class PriceFormatterTest extends TestCase
{
    protected PriceFormatter $priceFormatter;

    protected function setUp(): void
    {
        $this->priceFormatter = new PriceFormatter();
    }

    public static function dataProviderFormatPriceTest(): array
    {
        return [
            [1, null, '1.00'],
            [2.1, 'EUR', '2.10'],
            [3.54, null, '3.54'],
            [4.56789, 'USD', '4.57'],
            [-.000001, 'EUR', '0.00'],
            [3.54, 'JPY', '4'],
        ];
    }

    #[DataProvider('dataProviderFormatPriceTest')]
    public function testFormatPrice(float $input, ?string $currencyCode, string $output): void
    {
        static::assertSame($this->priceFormatter->formatPrice($input, $currencyCode), $output);
    }

    public static function dataProviderRoundPriceTest(): array
    {
        return [
            [1, 'EUR', 1],
            [2.1, 'EUR', 2.1],
            [3.54, null, 3.54],
            [4.56789, 'USD', 4.57],
            [-.000001, 'EUR', 0.00],
            [3.54, 'JPY', 4],
        ];
    }

    #[DataProvider('dataProviderRoundPriceTest')]
    public function testRoundPrice(float $input, ?string $currencyCode, float $output): void
    {
        static::assertSame($this->priceFormatter->roundPrice($input, $currencyCode), $output);
    }
}
