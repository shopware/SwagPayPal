<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Helper\Compatibility;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerGroup\CustomerGroupEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator as CoreGenerator;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\Tax\TaxCollection;

/**
 * @internal
 */
class Generator
{
    public static function createSalesChannelContext(
        ?Context $baseContext = null,
        ?CustomerGroupEntity $currentCustomerGroup = null,
        ?SalesChannelEntity $salesChannel = null,
        ?CurrencyEntity $currency = null,
        ?TaxCollection $taxes = null,
        ?CountryEntity $country = null,
        ?CountryStateEntity $state = null,
        ?CustomerAddressEntity $shipping = null,
        ?PaymentMethodEntity $paymentMethod = null,
        ?ShippingMethodEntity $shippingMethod = null,
        ?CustomerEntity $customer = null
    ): SalesChannelContext {
        $reflectionClass = new \ReflectionClass(CoreGenerator::class);
        $method = $reflectionClass->getMethod('createSalesChannelContext');
        $parameters = $method->getNumberOfParameters();
        if ($parameters === 12) {
            // fallback customer group has been removed with 6.4, we use the current one as duplicate here, as it isn't relevant in our tests

            return CoreGenerator::createSalesChannelContext(
                $baseContext,
                $currentCustomerGroup,
                $currentCustomerGroup,  // @phpstan-ignore-line
                $salesChannel,          // @phpstan-ignore-line
                $currency,              // @phpstan-ignore-line
                $taxes,                 // @phpstan-ignore-line
                $country,               // @phpstan-ignore-line
                $state,                 // @phpstan-ignore-line
                $shipping,              // @phpstan-ignore-line
                $paymentMethod,         // @phpstan-ignore-line
                $shippingMethod         // @phpstan-ignore-line
            );
        }

        return CoreGenerator::createSalesChannelContext(
            $baseContext,
            $currentCustomerGroup,
            $salesChannel,
            $currency,
            $taxes,
            $country,
            $state,
            $shipping,
            $paymentMethod,
            $shippingMethod,
            $customer
        );
    }

    public static function createCart(string $token): Cart
    {
        $reflectionMethod = new \ReflectionMethod(Cart::class, '__construct');
        $parameters = $reflectionMethod->getNumberOfParameters();
        if ($parameters === 2) {
            // @phpstan-ignore-next-line
            return new Cart($token, $token);
        }

        // @phpstan-ignore-next-line
        return new Cart($token);
    }
}
