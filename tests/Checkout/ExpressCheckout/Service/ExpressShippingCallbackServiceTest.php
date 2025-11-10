<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\Service;

if (!\class_exists('Shopware\Core\Test\Stub\Framework\IdsCollection')) {
    \class_alias('Shopware\Core\Framework\Test\IdsCollection', 'Shopware\Core\Test\Stub\Framework\IdsCollection');
}

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\Rule\CartAmountRule;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Test\Product\ProductBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Rule\Rule;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateCollection;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Stub\Framework\IdsCollection;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressShippingCallbackException;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ShippingOption;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\Api\OrderShippingCallback;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressShippingCallbackServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const BLOCK_AVAILABLITITY_RULE = [
        'name' => 'Block',
        'priority' => 0,
        'conditions' => [[
            'type' => CartAmountRule::RULE_NAME,
            'position' => 0,
            'value' => [
                'operator' => Rule::OPERATOR_EQ,
                'amount' => \PHP_INT_MAX,
            ],
        ]],
    ];

    private IdsCollection $ids;

    private SalesChannelContext $salesChannelContext;

    private ExpressShippingCallbackService $service;

    protected function setUp(): void
    {
        $country = $this->getCountry('DE');
        $shippingMethod = $this->getShippingMethod('shipping_standard');

        $this->ids = new IdsCollection();

        $this->getContainer()->get(SalesChannelContextPersister::class)->save(
            $this->ids->get('token'),
            [
                SalesChannelContextService::COUNTRY_ID => $country->getId(),
                SalesChannelContextService::COUNTRY_STATE_ID => (string) $country->getStates()?->first()?->getId(),
                SalesChannelContextService::SHIPPING_METHOD_ID => $shippingMethod->getId(),
            ],
            TestDefaults::SALES_CHANNEL,
        );

        $this->salesChannelContext = $this->getContainer()->get(SalesChannelContextService::class)->get(new SalesChannelContextServiceParameters(
            TestDefaults::SALES_CHANNEL,
            $this->ids->get('token'),
        ));

        $this->getContainer()->get(CartService::class)->add(
            new Cart($this->ids->get('token')),
            $this->getProductLineItem(),
            $this->salesChannelContext,
        );

        $this->service = $this->getContainer()->get(ExpressShippingCallbackService::class);
    }

    public function testCalculationWithChangedCountry(): void
    {
        $cart = $this->getCart();
        $country = $this->getCountry('GB');

        $order = $this->service->recalculateCart($this->createCallback($country), $this->salesChannelContext);

        static::assertSame('paypal-order-id', $order->getId());
        static::assertSame('default', $order->getPurchaseUnits()->first()?->getReferenceId());
        static::assertCount(2, $order->getPurchaseUnits()->first()->getShippingOptions() ?? []);
        static::assertNotSame($cart, $this->getCart());

        $this->assertContextParameters([
            SalesChannelContextService::COUNTRY_ID => $country->getId(),
            SalesChannelContextService::COUNTRY_STATE_ID => $country->getStates()?->first()?->getId(),
        ]);
    }

    public function testCalculationWithChangedCountryState(): void
    {
        $cart = $this->getCart();
        $country = $this->getCountry('DE');
        $last = $country->getStates()?->last();
        static::assertNotNull($last);
        $country->setStates(new CountryStateCollection([$last]));

        $order = $this->service->recalculateCart($this->createCallback($country), $this->salesChannelContext);

        static::assertSame('paypal-order-id', $order->getId());
        static::assertSame('default', $order->getPurchaseUnits()->first()?->getReferenceId());
        static::assertCount(2, $order->getPurchaseUnits()->first()->getShippingOptions() ?? []);
        static::assertNotSame($cart, $this->getCart());

        $this->assertContextParameters([
            SalesChannelContextService::COUNTRY_STATE_ID => $country->getStates()?->first()?->getId(),
        ]);
    }

    public function testCalculationWithChangedShippingMethod(): void
    {
        $cart = $this->getCart();
        $shippingMethod = $this->getShippingMethod('shipping_express');
        $country = $this->getCountry('DE');

        $order = $this->service->recalculateCart($this->createCallback($country, $shippingMethod->getId()), $this->salesChannelContext);

        static::assertSame('paypal-order-id', $order->getId());
        static::assertSame('default', $order->getPurchaseUnits()->first()?->getReferenceId());
        static::assertCount(2, $order->getPurchaseUnits()->first()->getShippingOptions() ?? []);
        static::assertNotSame($cart, $this->getCart());

        $this->assertContextParameters([
            SalesChannelContextService::SHIPPING_METHOD_ID => $shippingMethod->getId(),
        ]);
    }

    public function testCalculationWithoutChanges(): void
    {
        $cart = $this->getCart();
        $country = $this->getCountry('DE');
        $order = $this->service->recalculateCart($this->createCallback($country), $this->salesChannelContext);

        static::assertSame('paypal-order-id', $order->getId());
        static::assertSame('default', $order->getPurchaseUnits()->first()?->getReferenceId());
        static::assertCount(2, $order->getPurchaseUnits()->first()->getShippingOptions() ?? []);
        static::assertSame($cart, $this->getCart());

        $this->assertContextParameters([
            SalesChannelContextService::COUNTRY_ID => $country->getId(),
            SalesChannelContextService::COUNTRY_STATE_ID => $country->getStates()?->first()?->getId(),
            SalesChannelContextService::SHIPPING_METHOD_ID => $this->salesChannelContext->getShippingMethod()->getId(),
        ]);
    }

    public function testCalculationThrowsMethodNotAvailable(): void
    {
        $shippingMethod = $this->getShippingMethod('shipping_express');
        static::getContainer()->get('shipping_method.repository')->update([[
            'id' => $shippingMethod->getId(),
            'availabilityRule' => self::BLOCK_AVAILABLITITY_RULE,
        ]], $this->salesChannelContext->getContext());

        static::expectException(ExpressShippingCallbackException::class);
        static::expectExceptionMessage('Shipping method "Express" not available');

        $country = $this->getCountry('DE');
        $this->service->recalculateCart($this->createCallback($country, $shippingMethod->getId()), $this->salesChannelContext);
    }

    public function testCalculationThrowsAddressError(): void
    {
        $shippingMethod = $this->getShippingMethod('shipping_express');

        static::getContainer()->get('shipping_method.repository')->update([
            [
                'id' => $this->salesChannelContext->getShippingMethod()->getId(),
                'availabilityRule' => self::BLOCK_AVAILABLITITY_RULE,
            ],
            [
                'id' => $shippingMethod->getId(),
                'availabilityRule' => self::BLOCK_AVAILABLITITY_RULE,
            ],
        ], $this->salesChannelContext->getContext());

        static::expectException(ExpressShippingCallbackException::class);
        static::expectExceptionMessage('Address error for shipping to "DE"');

        $country = $this->getCountry('DE');
        $this->service->recalculateCart($this->createCallback($country, $shippingMethod->getId()), $this->salesChannelContext);
    }

    public function testCalculationThrowsCountryError(): void
    {
        static::expectException(ExpressShippingCallbackException::class);
        static::expectExceptionMessage('Country error for shipping to "sdf"');

        $country = $this->getCountry('DE');
        $callback = $this->createCallback($country);
        $callback->getShippingAddress()->setCountryCode('sdf');
        $this->service->recalculateCart($callback, $this->salesChannelContext);
    }

    private function assertContextParameters(array $expectedParams): void
    {
        $params = static::getContainer()->get(SalesChannelContextPersister::class)->load($this->ids->get('token'), TestDefaults::SALES_CHANNEL);

        static::assertEquals($expectedParams, \array_intersect_key($expectedParams, $params));
    }

    private function createCallback(
        CountryEntity $country,
        ?string $shippingMethodId = null,
    ): OrderShippingCallback {
        $option = new ShippingOption();
        $option->setId((string) $shippingMethodId);
        $option->setSelected(true);

        $address = new Address();
        $address->setCountryCode((string) $country->getIso());
        if ($countryState = $country->getStates()?->first()?->getShortCode()) {
            $address->setAdminArea1($countryState);
        }

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setReferenceId('default');

        $callback = new OrderShippingCallback();
        $callback->setId('paypal-order-id');
        $callback->setShippingAddress($address);
        $callback->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));
        if ($shippingMethodId) {
            $callback->setShippingOption($option);
        }

        return $callback;
    }

    private function getShippingMethod(string $technicalName): ShippingMethodEntity
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('technicalName', $technicalName))
            ->addFilter(new EqualsFilter('salesChannels.id', TestDefaults::SALES_CHANNEL));

        /** @var ShippingMethodEntity|null $shippingMethod */
        $shippingMethod = static::getContainer()->get('shipping_method.repository')->search($criteria, Context::createDefaultContext())->first();
        static::assertNotNull($shippingMethod);

        return $shippingMethod;
    }

    private function getCountry(string $iso): CountryEntity
    {
        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('iso', $iso))
            ->addFilter(new EqualsFilter('salesChannels.id', TestDefaults::SALES_CHANNEL))
            ->addAssociation('states');

        /** @var CountryEntity|null $country */
        $country = static::getContainer()->get('country.repository')->search($criteria, Context::createDefaultContext())->first();
        static::assertNotNull($country);
        static::assertNotNull($country->getStates()?->first());

        return $country;
    }

    private function getCart(): Cart
    {
        return static::getContainer()->get(CartService::class)->getCart($this->salesChannelContext->getToken(), $this->salesChannelContext);
    }

    private function getProductLineItem(): LineItem
    {
        $standardTaxId = static::getContainer()->get('tax.repository')->searchIds((new Criteria())->addFilter(new EqualsFilter('taxRate', 19.0)), Context::createDefaultContext())->firstId();
        static::assertNotNull($standardTaxId, 'Tax "Standard rate" is missing');
        $this->ids->set('standard-tax-rate', $standardTaxId);

        /** @phpstan-ignore-next-line new.internalClass - it's a test */
        $product = (new ProductBuilder($this->ids, 'P-10000'))
            ->price(10)
            ->visibility()
            ->tax('standard-tax-rate', 19)
            ->build();

        static::getContainer()->get('product.repository')->create([$product], $this->salesChannelContext->getContext());

        return static::getContainer()->get(LineItemFactoryRegistry::class)->create([
            'type' => 'product',
            'id' => $this->ids->get('P-10000'),
        ], $this->salesChannelContext);
    }
}
