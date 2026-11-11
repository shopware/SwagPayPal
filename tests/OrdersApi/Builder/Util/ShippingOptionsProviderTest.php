<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractShippingCostRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\ShippingCostRouteResponse;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\OrdersApi\Builder\Util\ShippingOptionsProvider;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ShippingOptionsProvider::class)]
class ShippingOptionsProviderTest extends TestCase
{
    public function testGetShippingOptionsCreatesAnOptionPerShippingMethod(): void
    {
        $provider = $this->createProvider([$this->createShippingMethod('standard-id', 'Standard'), $this->createShippingMethod('express-id', 'Express')]);

        $options = $provider->getShippingOptions($this->createCart(), $this->createSalesChannelContext('unselected-id'));

        static::assertCount(2, $options);

        $first = $options->first();
        static::assertNotNull($first);
        static::assertSame('standard-id', $first->getId());
        static::assertSame('Standard', $first->getLabel());
    }

    public function testGetShippingOptionsMarksTheSelectedMethodAndSetsItsGrossAmount(): void
    {
        $provider = $this->createProvider([
            $this->createShippingMethod('selected-id', 'Selected'),
            $this->createShippingMethod('other-id', 'Other'),
        ]);

        $cart = $this->createCart(shippingTotal: 4.99);
        $options = $provider->getShippingOptions($cart, $this->createSalesChannelContext('selected-id'));

        $selectedOption = $options->get('selected-id');
        static::assertNotNull($selectedOption);
        static::assertTrue($selectedOption->isSelected());
        static::assertSame('4.99', $selectedOption->getAmount()->getValue());
        static::assertSame('EUR', $selectedOption->getAmount()->getCurrencyCode());

        $otherOption = $options->get('other-id');
        static::assertNotNull($otherOption);
        static::assertFalse($otherOption->isSelected());
    }

    public function testGetShippingOptionsAddsTaxesToTheSelectedMethodOnNetCarts(): void
    {
        $provider = $this->createProvider([$this->createShippingMethod('selected-id', 'Selected')]);

        $cart = $this->createCart(shippingTotal: 5.0, shippingTax: 0.95, taxState: CartPrice::TAX_STATE_NET);
        $options = $provider->getShippingOptions($cart, $this->createSalesChannelContext('selected-id'));

        $selectedOption = $options->first();
        static::assertNotNull($selectedOption);
        static::assertTrue($selectedOption->isSelected());
        static::assertSame('5.95', $selectedOption->getAmount()->getValue());
    }

    public function testGetShippingOptionsSetsTheAmountsOfUnselectedMethodsFromTheShippingCostRoute(): void
    {
        $cart = $this->createCart(shippingTotal: 4.99);
        $context = $this->createSalesChannelContext('selected-id');

        $shippingCostRoute = $this->createShippingCostRoute(['express-id' => 12.5, 'pickup-id' => 0.0]);
        $shippingCostRoute
            ->expects($this->once())
            ->method('shippingCostsCart')
            ->with($cart, $context);

        $provider = $this->createProvider([
            $this->createShippingMethod('selected-id', 'Selected'),
            $this->createShippingMethod('express-id', 'Express'),
            $this->createShippingMethod('pickup-id', 'Pickup'),
            $this->createShippingMethod('unknown-id', 'Unknown'),
        ], $shippingCostRoute);

        $options = $provider->getShippingOptions($cart, $context);

        static::assertSame('4.99', $options->get('selected-id')?->getAmount()->getValue());

        $expressOption = $options->get('express-id');
        static::assertNotNull($expressOption);
        static::assertFalse($expressOption->isSelected());
        static::assertSame('12.50', $expressOption->getAmount()->getValue());
        static::assertSame('EUR', $expressOption->getAmount()->getCurrencyCode());

        static::assertSame('0.00', $options->get('pickup-id')?->getAmount()->getValue());
        static::assertFalse($options->get('unknown-id')?->isset('amount'));
    }

    public function testGetShippingOptionsAddsTaxesToUnselectedMethodsOnNetCarts(): void
    {
        $provider = $this->createProvider([
            $this->createShippingMethod('selected-id', 'Selected'),
            $this->createShippingMethod('express-id', 'Express'),
        ], $this->createShippingCostRoute(['express-id' => 10.0], shippingTax: 1.9));

        $cart = $this->createCart(shippingTotal: 5.0, shippingTax: 0.95, taxState: CartPrice::TAX_STATE_NET);
        $options = $provider->getShippingOptions($cart, $this->createSalesChannelContext('selected-id'));

        static::assertSame('11.90', $options->get('express-id')?->getAmount()->getValue());
    }

    #[DataProvider('dataProviderVersionsWithoutShippingCostRouteFix')]
    public function testGetShippingOptionsDoesNotUseTheShippingCostRouteBeforeItsFix(string $shopwareVersion): void
    {
        $shippingCostRoute = $this->createShippingCostRoute(['express-id' => 12.5]);
        $shippingCostRoute
            ->expects($this->never())
            ->method('shippingCostsCart');

        $provider = $this->createProvider([
            $this->createShippingMethod('selected-id', 'Selected'),
            $this->createShippingMethod('express-id', 'Express'),
        ], $shippingCostRoute, $shopwareVersion);

        $options = $provider->getShippingOptions($this->createCart(shippingTotal: 4.99), $this->createSalesChannelContext('selected-id'));

        static::assertSame('4.99', $options->get('selected-id')?->getAmount()->getValue());
        static::assertFalse($options->get('express-id')?->isset('amount'));
    }

    public static function dataProviderVersionsWithoutShippingCostRouteFix(): \Generator
    {
        yield 'route introduced' => ['6.7.11.0'];
        yield 'last version before the fix' => ['6.7.13.1'];
    }

    public function testGetShippingOptionsWithoutShippingCostRoute(): void
    {
        $provider = $this->createProvider([
            $this->createShippingMethod('selected-id', 'Selected'),
            $this->createShippingMethod('express-id', 'Express'),
        ], shopwareVersion: '6.7.0.0');

        $options = $provider->getShippingOptions($this->createCart(shippingTotal: 4.99), $this->createSalesChannelContext('selected-id'));

        static::assertSame('4.99', $options->get('selected-id')?->getAmount()->getValue());
        static::assertFalse($options->get('express-id')?->isset('amount'));
    }

    public function testGetShippingOptionsReturnsEmptyCollectionWhenNoMethodsAreAvailable(): void
    {
        $provider = $this->createProvider();

        $options = $provider->getShippingOptions($this->createCart(), $this->createSalesChannelContext('selected-id'));

        static::assertCount(0, $options);
    }

    /**
     * @param list<ShippingMethodEntity> $shippingMethods
     */
    private function createProvider(
        array $shippingMethods = [],
        ?AbstractShippingCostRoute $shippingCostRoute = null,
        string $shopwareVersion = '6.7.14.0',
    ): ShippingOptionsProvider {
        $response = $this->createMock(ShippingMethodRouteResponse::class);
        $response
            ->method('getShippingMethods')
            ->willReturn(new ShippingMethodCollection($shippingMethods));

        $shippingMethodRoute = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingMethodRoute
            ->method('load')
            ->willReturn($response);

        return new ShippingOptionsProvider(new PriceFormatter(), $shippingMethodRoute, $shippingCostRoute, $shopwareVersion);
    }

    /**
     * @param array<string, float> $shippingTotals
     */
    private function createShippingCostRoute(array $shippingTotals, float $shippingTax = 0.0): AbstractShippingCostRoute&MockObject
    {
        // @deprecated tag:v11.0.0 - class exists with v6.7.11.0.
        if (!\class_exists(AbstractShippingCostRoute::class)) {
            static::markTestSkipped('AbstractShippingCostRoute does not exist');
        }

        $response = $this->createMock(ShippingCostRouteResponse::class);
        $response
            ->method('getShippingCost')
            ->willReturnCallback(static fn (string $shippingMethodId) => isset($shippingTotals[$shippingMethodId]) ? new CalculatedPrice(
                $shippingTotals[$shippingMethodId],
                $shippingTotals[$shippingMethodId],
                new CalculatedTaxCollection([new CalculatedTax($shippingTax, 19.0, $shippingTotals[$shippingMethodId])]),
                new TaxRuleCollection(),
            ) : null);

        $shippingCostRoute = $this->createMock(AbstractShippingCostRoute::class);
        $shippingCostRoute
            ->method('shippingCostsCart')
            ->willReturn($response);

        return $shippingCostRoute;
    }

    private function createShippingMethod(string $id, string $name): ShippingMethodEntity
    {
        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId($id);
        $shippingMethod->setName($name);
        $shippingMethod->addTranslated('name', $name);

        return $shippingMethod;
    }

    private function createCart(
        float $shippingTotal = 0.0,
        float $shippingTax = 0.0,
        string $taxState = CartPrice::TAX_STATE_GROSS,
    ): Cart {
        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice(new CartPrice(0.0, 0.0, 0.0, new CalculatedTaxCollection(), new TaxRuleCollection(), $taxState));

        $shippingCosts = new CalculatedPrice(
            $shippingTotal,
            $shippingTotal,
            new CalculatedTaxCollection([new CalculatedTax($shippingTax, 19.0, $shippingTotal)]),
            new TaxRuleCollection(),
        );

        // Cart::getShippingCosts() is derived from the deliveries, so a delivery
        // carrying the shipping costs is added rather than set directly.
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());

        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $cart->addDeliveries(new DeliveryCollection([
            new Delivery(
                new DeliveryPositionCollection(),
                new DeliveryDate($now, $now),
                new ShippingMethodEntity(),
                ShippingLocation::createFromCountry($country),
                $shippingCosts,
            ),
        ]));

        return $cart;
    }

    private function createSalesChannelContext(string $selectedShippingMethodId): SalesChannelContext
    {
        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId($selectedShippingMethodId);

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getCurrency')
            ->willReturn($currency);
        $salesChannelContext
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        return $salesChannelContext;
    }
}
