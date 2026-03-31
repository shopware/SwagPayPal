<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Cart\Service;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
class CartPriceServiceTest extends TestCase
{
    private PriceFormatter $priceFormatter;

    private CartPriceService $cartPriceService;

    protected function setUp(): void
    {
        $this->priceFormatter = new PriceFormatter();
        $this->cartPriceService = new CartPriceService($this->priceFormatter);
    }

    #[DataProvider('hasZeroPriceProvider')]
    public function testHasZeroPrice(bool $expected, float $totalPrice, string $currencyIso, bool $withLineItem): void
    {
        $cart = $this->createCart($totalPrice, $withLineItem);

        static::assertSame($expected, $this->cartPriceService->hasZeroPrice($cart, $this->createSalesChannelContext($currencyIso)));
    }

    /**
     * @return iterable<string, array{expected: bool, totalPrice: float, currencyIso: string, withLineItem: bool}>
     */
    public static function hasZeroPriceProvider(): iterable
    {
        yield 'empty carts are never treated as zero-value carts' => [
            'expected' => false,
            'totalPrice' => 0.0,
            'currencyIso' => 'EUR',
            'withLineItem' => false,
        ];

        yield 'positive totals are not zero-value carts' => [
            'expected' => false,
            'totalPrice' => 10.99,
            'currencyIso' => 'EUR',
            'withLineItem' => true,
        ];

        yield 'rounded zero totals are treated as zero-value carts' => [
            'expected' => true,
            'totalPrice' => 0.004,
            'currencyIso' => 'EUR',
            'withLineItem' => true,
        ];

        yield 'negative totals are treated as zero-value carts' => [
            'expected' => true,
            'totalPrice' => -5.0,
            'currencyIso' => 'EUR',
            'withLineItem' => true,
        ];

        yield 'jpy totals are rounded using zero decimals' => [
            'expected' => true,
            'totalPrice' => 0.4,
            'currencyIso' => 'JPY',
            'withLineItem' => true,
        ];

        yield 'jpy totals above zero after rounding are not zero-value carts' => [
            'expected' => false,
            'totalPrice' => 0.5,
            'currencyIso' => 'JPY',
            'withLineItem' => true,
        ];
    }

    private function createCart(float $totalPrice, bool $withLineItem): Cart
    {
        $cart = new Cart('test-token');
        $cart->setPrice(new CartPrice(
            $totalPrice,
            $totalPrice,
            $totalPrice,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        if ($withLineItem) {
            $cart->add(new LineItem('line-item', LineItem::PRODUCT_LINE_ITEM_TYPE));
        }

        return $cart;
    }

    private function createSalesChannelContext(string $currencyIso): SalesChannelContext
    {
        $currency = new CurrencyEntity();
        $currency->setIsoCode($currencyIso);

        return Generator::createSalesChannelContext(currency: $currency);
    }
}
