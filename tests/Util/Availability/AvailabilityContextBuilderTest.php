<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Availability;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Product\State;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Util\Availability\AvailabilityContextBuilder;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(AvailabilityContextBuilder::class)]
class AvailabilityContextBuilderTest extends TestCase
{
    public function testBuildAvailabilityContext(): void
    {
        $cart = $this->createMock(Cart::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $customer = $this->createMock(CustomerEntity::class);
        $address = $this->createMock(CustomerAddressEntity::class);
        $country = $this->createMock(CountryEntity::class);
        $currency = $this->createMock(CurrencyEntity::class);
        $cartPrice = $this->createMock(CartPrice::class);
        $lineItemCollection = $this->createMock(LineItemCollection::class);

        $country->method('getIso')->willReturn('US');
        $address->method('getCountry')->willReturn($country);
        $customer->method('getActiveBillingAddress')->willReturn($address);
        $salesChannelContext->method('getCustomer')->willReturn($customer);
        $salesChannelContext->method('getCurrency')->willReturn($currency);
        $currency->method('getIsoCode')->willReturn('USD');
        $cart->method('getPrice')->willReturn($cartPrice);
        $cartPrice->method('getTotalPrice')->willReturn(100.00);
        $cart->method('getLineItems')->willReturn($lineItemCollection);
        $lineItemCollection->method('hasLineItemWithState')->with(State::IS_DOWNLOAD)->willReturn(true);

        $context = AvailabilityContextBuilder::buildAvailabilityContext($cart, $salesChannelContext);

        static::assertEquals('US', $context->getBillingCountryCode());
        static::assertEquals('USD', $context->getCurrencyCode());
        static::assertEquals(100.00, $context->getTotalAmount());
        static::assertTrue($context->hasDigitalProducts());
    }

    public function testBuildAvailabilityContextWithoutCustomer(): void
    {
        $cart = $this->createMock(Cart::class);
        $salesChannelContext = $this->createMock(SalesChannelContext::class);

        $country = $this->createMock(CountryEntity::class);
        $currency = $this->createMock(CurrencyEntity::class);
        $cartPrice = $this->createMock(CartPrice::class);
        $lineItemCollection = $this->createMock(LineItemCollection::class);
        $shippingLocation = $this->createMock(ShippingLocation::class);

        $country->method('getIso')->willReturn('US');
        $salesChannelContext->method('getCustomer')->willReturn(null);
        $salesChannelContext->method('getShippingLocation')->willReturn($shippingLocation);
        $salesChannelContext->method('getCurrency')->willReturn($currency);
        $shippingLocation->method('getCountry')->willReturn($country);
        $currency->method('getIsoCode')->willReturn('USD');
        $cart->method('getPrice')->willReturn($cartPrice);
        $cartPrice->method('getTotalPrice')->willReturn(100.00);
        $cart->method('getLineItems')->willReturn($lineItemCollection);
        $lineItemCollection->method('hasLineItemWithState')->with(State::IS_DOWNLOAD)->willReturn(true);

        $context = AvailabilityContextBuilder::buildAvailabilityContext($cart, $salesChannelContext);

        static::assertEquals('US', $context->getBillingCountryCode());
        static::assertEquals('USD', $context->getCurrencyCode());
        static::assertEquals(100.00, $context->getTotalAmount());
        static::assertTrue($context->hasDigitalProducts());
    }
}
