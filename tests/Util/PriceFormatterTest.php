<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
class PriceFormatterTest extends TestCase
{
    protected PriceFormatter $priceFormatter;

    public function setUp(): void
    {
        $this->priceFormatter = new PriceFormatter();
    }

    public function dataProviderFormatPriceTest(): array
    {
        return [
            [1, '1.00'],
            [2.1, '2.10'],
            [3.54, '3.54'],
            [4.56789, '4.57'],
            [-.000001, '0.00'],
        ];
    }

    /**
     * @dataProvider dataProviderFormatPriceTest
     */
    public function testFormatPrice(float $input, string $output): void
    {
        static::assertSame($this->priceFormatter->formatPrice($input), $output);
    }

    public function dataProviderRoundPriceTest(): array
    {
        return [
            [1, 1],
            [2.1, 2.1],
            [3.54, 3.54],
            [4.56789, 4.57],
            [-.000001, 0.00],
        ];
    }

    /**
     * @dataProvider dataProviderRoundPriceTest
     */
    public function testRoundPrice(float $input, float $output): void
    {
        static::assertSame($this->priceFormatter->roundPrice($input), $output);
    }
}
