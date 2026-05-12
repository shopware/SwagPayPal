<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Availability;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Util\Availability\AvailabilityContextBuilder;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(AvailabilityContextBuilder::class)]
class AvailabilityContextBuilderTest extends TestCase
{
    public function testBuildFromCart(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setIso('US');

        $address = new CustomerAddressEntity();
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setActiveBillingAddress($address);

        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('USD');

        $cart = Generator::createCart();

        $salesChannelContext = Generator::generateSalesChannelContext(currency: $currency, customer: $customer);

        $context = AvailabilityContextBuilder::buildFromCart($cart, $salesChannelContext);

        static::assertSame('US', $context->getBillingCountryCode());
        static::assertSame('USD', $context->getCurrencyCode());
        static::assertSame(275.00, $context->getTotalAmount());
        static::assertFalse($context->hasDigitalProducts());
    }

    public function testBuildFromCartWithoutCustomer(): void
    {
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('USD');

        $cart = Generator::createCart();

        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setIso('US');

        $shippingLocation = new ShippingLocation($country, null, null);

        $salesChannelContext = Generator::generateSalesChannelContext(
            currency: $currency,
            shippingLocation: $shippingLocation,
            customer: new CustomerEntity()
        );

        $context = AvailabilityContextBuilder::buildFromCart($cart, $salesChannelContext);

        static::assertSame('US', $context->getBillingCountryCode());
        static::assertSame('USD', $context->getCurrencyCode());
        static::assertSame(275.00, $context->getTotalAmount());
        static::assertFalse($context->hasDigitalProducts());
    }

    public function testBuildFromProduct(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setIso('US');

        $address = new CustomerAddressEntity();
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setActiveBillingAddress($address);

        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('USD');

        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice(new CalculatedPrice(275, 275, new CalculatedTaxCollection(), new TaxRuleCollection(), 1));

        $salesChannelContext = Generator::generateSalesChannelContext(currency: $currency, customer: $customer);

        $context = AvailabilityContextBuilder::buildFromProduct($product, $salesChannelContext);

        static::assertSame('US', $context->getBillingCountryCode());
        static::assertSame('USD', $context->getCurrencyCode());
        static::assertSame(275.00, $context->getTotalAmount());
        static::assertFalse($context->hasDigitalProducts());
    }

    public function testBuildFromProductWithoutCustomer(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setIso('US');

        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('USD');

        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice(new CalculatedPrice(275, 275, new CalculatedTaxCollection(), new TaxRuleCollection(), 1));

        $shippingLocation = new ShippingLocation($country, null, null);

        $salesChannelContext = Generator::generateSalesChannelContext(
            currency: $currency,
            shippingLocation: $shippingLocation,
            customer: new CustomerEntity()
        );

        $context = AvailabilityContextBuilder::buildFromProduct($product, $salesChannelContext);

        static::assertSame('US', $context->getBillingCountryCode());
        static::assertSame('USD', $context->getCurrencyCode());
        static::assertSame(275.00, $context->getTotalAmount());
        static::assertFalse($context->hasDigitalProducts());
    }

    public function testBuildFromOrder(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setIso('US');

        $address = new CustomerAddressEntity();
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);

        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setActiveBillingAddress($address);

        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('USD');

        $order = new OrderEntity();
        $order->setPrice(new CartPrice(275, 275, 1, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        $salesChannelContext = Generator::generateSalesChannelContext(currency: $currency, customer: $customer);

        $context = AvailabilityContextBuilder::buildFromOrder($order, $salesChannelContext);

        static::assertSame('US', $context->getBillingCountryCode());
        static::assertSame('USD', $context->getCurrencyCode());
        static::assertSame(275.00, $context->getTotalAmount());
        static::assertFalse($context->hasDigitalProducts());
    }

    public function testBuildFromOrderWithoutCustomer(): void
    {
        $country = new CountryEntity();
        $country->setId(Uuid::randomHex());
        $country->setIso('US');

        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('USD');

        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice(new CalculatedPrice(275, 275, new CalculatedTaxCollection(), new TaxRuleCollection(), 1));

        $customerAddress = new CustomerAddressEntity();
        $customerAddress->setCountry($country);

        $shippingLocation = new ShippingLocation($country, null, null);

        $salesChannelContext = Generator::generateSalesChannelContext(
            currency: $currency,
            shippingLocation: $shippingLocation,
            customer: new CustomerEntity()
        );

        $order = new OrderEntity();
        $order->setPrice(new CartPrice(275, 275, 1, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_GROSS));

        $context = AvailabilityContextBuilder::buildFromOrder($order, $salesChannelContext);

        static::assertSame('US', $context->getBillingCountryCode());
        static::assertSame('USD', $context->getCurrencyCode());
        static::assertSame(275.00, $context->getTotalAmount());
        static::assertFalse($context->hasDigitalProducts());
    }
}
