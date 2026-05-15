<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgenticCommerce\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionCartAddedInformationError;
use Shopware\Core\Checkout\Shipping\SalesChannel\AbstractShippingMethodRoute;
use Shopware\Core\Checkout\Shipping\SalesChannel\ShippingMethodRouteResponse;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Content\Product\Cart\ProductNotFoundError;
use Shopware\Core\Content\Product\Cart\PurchaseStepsError;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\PartialEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\DeliveryTime\DeliveryTimeEntity;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\Locale\LocaleDefinition;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\Context\LanguageInfo;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Struct\V1\Address;
use Swag\PayPal\AgenticCommerce\Struct\V1\AppliedCoupon;
use Swag\PayPal\AgenticCommerce\Struct\V1\AppliedCouponCollection;
use Swag\PayPal\AgenticCommerce\Struct\V1\BillingAddress;
use Swag\PayPal\AgenticCommerce\Struct\V1\CartItem;
use Swag\PayPal\AgenticCommerce\Struct\V1\CartItemCollection;
use Swag\PayPal\AgenticCommerce\Struct\V1\CartTotals;
use Swag\PayPal\AgenticCommerce\Struct\V1\Customer;
use Swag\PayPal\AgenticCommerce\Struct\V1\Money;
use Swag\PayPal\AgenticCommerce\Struct\V1\PayPalCart;
use Swag\PayPal\AgenticCommerce\Struct\V1\ShippingAddress;
use Swag\PayPal\AgenticCommerce\Struct\V1\ShippingOption;
use Swag\PayPal\AgenticCommerce\Struct\V1\ShippingOptionCollection;
use Swag\PayPal\AgenticCommerce\Struct\V1\ValidationIssue;
use Swag\PayPal\AgenticCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\AgenticCommerce\Validation\ValidationIssues;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayPalCartTransformer::class)]
class PayPalCartTransformerTest extends TestCase
{
    public function testConvertToPayPalCart(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $country = new CountryEntity();
        $country->setIso('DE');

        $address = new CustomerAddressEntity();
        $address->setCountry($country);
        $address->setZipcode('12345');
        $address->setStreet('Mainstreet 1');
        $address->setCity('City 1');

        $customer = self::createCustomer(null);
        $customer->setDefaultShippingAddress($address);
        $customer->setDefaultBillingAddress($address);

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCurrency')
            ->willReturn($currency);
        $context
            ->method('getCustomer')
            ->willReturn($customer);

        $cart = new Cart('some-token');

        $payPalCart = $transformer->convertToPayPalCart($cart, $context);

        static::assertSame('CART-some-token', $payPalCart->getId());
        static::assertSame(PayPalCart::VALIDATION_STATUS__VALID, $payPalCart->getValidationStatus());
        static::assertInstanceOf(CartTotals::class, $payPalCart->getTotals());
        static::assertInstanceOf(ShippingOptionCollection::class, $payPalCart->getAvailableShippingOptions());
        static::assertInstanceOf(Customer::class, $payPalCart->getCustomer());
        static::assertInstanceOf(Address::class, $payPalCart->getShippingAddress());
        static::assertInstanceOf(Address::class, $payPalCart->getBillingAddress());
        static::assertTrue($payPalCart->isset('validationIssues'));
        static::assertTrue($payPalCart->isset('items'));
    }

    public function testConvertToCartItems(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCurrency')
            ->willReturn($currency);

        $noPriceId = Uuid::randomHex();
        $lineItem1 = new LineItem($noPriceId, 'product', $noPriceId, 10);
        $lineItem1->setLabel('Item Label');

        $noParentId = Uuid::randomHex();
        $lineItem2 = new LineItem($noParentId, 'product', $noParentId, 10);
        $lineItem2->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem2->setLabel('Item Label');

        $withParentId = Uuid::randomHex();
        $lineItem3 = new LineItem($withParentId, 'product', $withParentId, 10);
        $lineItem3->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem3->setPayloadValue('parentId', 'someParentId');
        $lineItem3->setLabel('Item Label');

        $cartItems = $transformer->convertToCartItems([$lineItem1, $lineItem2, $lineItem3], $context);
        static::assertCount(2, $cartItems);

        $noParentItem = $cartItems->get(0);
        $withParentItem = $cartItems->get(1);

        static::assertInstanceOf(CartItem::class, $noParentItem);
        static::assertInstanceOf(CartItem::class, $withParentItem);

        static::assertSame('Item Label', $noParentItem->getName());
        static::assertSame($noParentId, $noParentItem->getVariantId());
        static::assertSame(10, $noParentItem->getQuantity());
        static::assertNull($noParentItem->getParentId());
        static::assertSame('100', $noParentItem->getPrice()?->getValue());
        static::assertSame('EUR', $noParentItem->getPrice()->getCurrencyCode());

        static::assertSame('Item Label', $withParentItem->getName());
        static::assertSame($withParentId, $withParentItem->getVariantId());
        static::assertSame(10, $withParentItem->getQuantity());
        static::assertSame('someParentId', $withParentItem->getParentId());
        static::assertSame('100', $withParentItem->getPrice()?->getValue());
        static::assertSame('EUR', $withParentItem->getPrice()->getCurrencyCode());
    }

    public function testConvertToAvailableShippingMethods(): void
    {
        $deliveryTime = new DeliveryTimeEntity();
        $deliveryTime->setId(Uuid::randomHex());
        $deliveryTime->setTranslated(['name' => 'DeliveryTime']);
        $deliveryTime->setUnit(DeliveryTimeEntity::DELIVERY_TIME_DAY);
        $deliveryTime->setMin(1);
        $deliveryTime->setMax(2);

        $shippingMethodId1 = Uuid::randomHex();
        $shippingMethodId2 = Uuid::randomHex();
        $shippingMethodId3 = Uuid::randomHex();
        $shippingMethod1 = new ShippingMethodEntity();
        $shippingMethod1->setId($shippingMethodId1);
        $shippingMethod1->setTranslated(['name' => 'Label 1', 'description' => 'Description 1']);
        $shippingMethod1->setDeliveryTime($deliveryTime);
        $shippingMethod2 = new ShippingMethodEntity();
        $shippingMethod2->setId($shippingMethodId2);
        $shippingMethod2->setTranslated(['name' => 'Label 2', 'description' => 'Description 2']);
        $shippingMethod2->setDeliveryTime($deliveryTime);
        $shippingMethod3 = new ShippingMethodEntity();
        $shippingMethod3->setId($shippingMethodId3);
        $shippingMethod3->setTranslated(['name' => 'Label 3', 'description' => 'Description 3']);
        $shippingMethod3->setDeliveryTime($deliveryTime);

        $result = new EntitySearchResult(
            'shipping_method',
            3,
            new ShippingMethodCollection([$shippingMethod1, $shippingMethod2, $shippingMethod3]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $shippingRouteMock = $this->createMock(AbstractShippingMethodRoute::class);
        $shippingRouteMock
            ->expects(static::once())
            ->method('load')
            ->willReturn(new ShippingMethodRouteResponse($result));

        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $shippingRouteMock,
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCurrency')
            ->willReturn($currency);

        $delivery1 = clone $delivery2 = clone $delivery3 = new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(new \DateTime(), new \DateTime()),
            new ShippingMethodEntity(),
            new ShippingLocation(new CountryEntity(), null, null),
            new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection())
        );

        $delivery1->setShippingMethod($shippingMethod1);
        $delivery2->setShippingMethod($shippingMethod2);
        $delivery3->setShippingMethod(clone $shippingMethod1);

        $cart = new Cart(Uuid::randomHex());
        $cart->setDeliveries(new DeliveryCollection([$delivery1, $delivery2, $delivery3]));

        $availableShippingMethods = $transformer->convertToAvailableShippingMethods($cart, $context);
        $first = $availableShippingMethods->get(0);
        $second = $availableShippingMethods->get(1);
        $third = $availableShippingMethods->get(2);

        // TODO: reintroduce once we have a solution for providing all shipping methods including prices
        // static::assertCount(3, $availableShippingMethods);
        static::assertCount(2, $availableShippingMethods);

        static::assertInstanceOf(ShippingOption::class, $first);
        static::assertInstanceOf(ShippingOption::class, $second);
        // TODO: reintroduce once we have a solution for providing all shipping methods including prices
        // static::assertInstanceOf(ShippingOption::class, $third);
        static::assertNull($third);

        static::assertSame($shippingMethodId1, $first->getId());
        static::assertSame('Label 1 (DeliveryTime)', $first->getName());
        static::assertSame('Description 1', $first->getDescription());
        static::assertTrue($first->isset('price'));
        static::assertTrue($first->isSelected());
        static::assertSame('20', $first->getPrice()->getValue());
        static::assertSame('EUR', $first->getPrice()->getCurrencyCode());
        static::assertNotNull($first->getEstimatedDelivery());

        static::assertSame($shippingMethodId2, $second->getId());
        static::assertSame('Label 2 (DeliveryTime)', $second->getName());
        static::assertSame('Description 2', $second->getDescription());
        static::assertTrue($second->isset('price'));
        static::assertTrue($second->isSelected());
        static::assertSame('10', $second->getPrice()->getValue());
        static::assertSame('EUR', $second->getPrice()->getCurrencyCode());
        static::assertNotNull($second->getEstimatedDelivery());

        // TODO: reintroduce once we have a solution for providing all shipping methods including prices
        // static::assertSame($shippingMethodId3, $third->getId());
        // static::assertSame('Label 3 (DeliveryTime)', $third->getName());
        // static::assertSame('Description 3', $third->getDescription());
        // static::assertFalse($third->isset('price'));
        // static::assertFalse($third->isSelected());
        // static::assertNotNull($third->getEstimatedDelivery());
    }

    public function testConvertNullCustomer(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        static::assertNull($transformer->convertCustomer(null));
    }

    public function testConvertCustomerNoPhoneNumber(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $converted = $transformer->convertCustomer(self::createCustomer(null));

        static::assertInstanceOf(Customer::class, $converted);
        static::assertSame('Max', $converted->getName()->getGivenName());
        static::assertSame('Mustermann', $converted->getName()->getSurname());
        static::assertNull($converted->getPhone());
    }

    public function testConvertCustomerValidPhoneNumber(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $converted = $transformer->convertCustomer(self::createCustomer('+12 12345-67890'));

        static::assertInstanceOf(Customer::class, $converted);
        static::assertSame('Max', $converted->getName()->getGivenName());
        static::assertSame('Mustermann', $converted->getName()->getSurname());
        static::assertSame('+12 12345-67890', $converted->getPhone()?->getFullPhoneNumber());
        static::assertSame('12', $converted->getPhone()->getCountryCode());
        static::assertSame('12345', $converted->getPhone()->getNationalNumber());
        static::assertSame('67890', $converted->getPhone()->getExtensionNumber());
    }

    public function testConvertCustomerInvalidPhoneNumber(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $converted = $transformer->convertCustomer(self::createCustomer('1234567890'));

        static::assertInstanceOf(Customer::class, $converted);
        static::assertSame('Max', $converted->getName()->getGivenName());
        static::assertSame('Mustermann', $converted->getName()->getSurname());
        static::assertNull($converted->getPhone());
    }

    public function testCreateTotals(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCurrency')
            ->willReturn($currency);

        $totals = $transformer->createTotals($this->createCart(), $context);

        static::assertSame('26', $totals->getTax()?->getValue());
        static::assertSame('EUR', $totals->getTax()->getCurrencyCode());
        static::assertSame('226', $totals->getTotal()->getValue());
        static::assertSame('EUR', $totals->getTotal()->getCurrencyCode());
        static::assertSame('15', $totals->getShipping()?->getValue());
        static::assertSame('EUR', $totals->getShipping()->getCurrencyCode());
        static::assertSame('200', $totals->getSubtotal()?->getValue());
        static::assertSame('EUR', $totals->getSubtotal()->getCurrencyCode());
        static::assertSame('15', $totals->getDiscount()?->getValue());
        static::assertSame('EUR', $totals->getDiscount()->getCurrencyCode());
    }

    public function testConvertAddressNoIsoFound(): void
    {
        $this->expectException(AgentException::class);

        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $address = new CustomerAddressEntity();
        $address->setCountryId(Uuid::randomHex());

        $transformer->convertAddress($address, ShippingAddress::class, Context::createDefaultContext());
    }

    public function testConvertNullAddress(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        static::assertNull($transformer->convertAddress(null, ShippingAddress::class, Context::createDefaultContext()));
    }

    public function testConvertAddress(): void
    {
        $entity = new PartialEntity();
        $entity->setUniqueIdentifier(Uuid::randomHex());
        $entity->set('iso', 'DE');

        $result = new EntitySearchResult(
            'country',
            1,
            new EntityCollection([$entity]),
            null,
            new Criteria(),
            Context::createDefaultContext()
        );

        $repository = $this->createMock(EntityRepository::class);
        $repository
            ->method('search')
            ->willReturn($result);

        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $repository,
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $countryState = new CountryStateEntity();
        $countryState->setShortCode('DE-NW');

        $address = new CustomerAddressEntity();
        $address->setCountryId(Uuid::randomHex());
        $address->setZipcode('12345');
        $address->setStreet('Mainstreet 1');
        $address->setCity('City 1');
        $address->setAdditionalAddressLine1('Address line 1');
        $address->setCountryState($countryState);

        $shippingAddress = $transformer->convertAddress($address, ShippingAddress::class, Context::createDefaultContext());
        $billingAddress = $transformer->convertAddress($address, BillingAddress::class, Context::createDefaultContext());

        static::assertInstanceOf(ShippingAddress::class, $shippingAddress);
        static::assertInstanceOf(BillingAddress::class, $billingAddress);

        static::assertSame('12345', $shippingAddress->getPostalCode());
        static::assertSame('12345', $billingAddress->getPostalCode());

        static::assertSame('Mainstreet 1', $shippingAddress->getAddressLine1());
        static::assertSame('Mainstreet 1', $billingAddress->getAddressLine1());

        static::assertSame('City 1', $shippingAddress->getAdminArea2());
        static::assertSame('City 1', $billingAddress->getAdminArea2());

        static::assertSame('Address line 1', $shippingAddress->getAddressLine2());
        static::assertSame('Address line 1', $billingAddress->getAddressLine2());

        static::assertSame('DE', $shippingAddress->getCountryCode());
        static::assertSame('DE', $billingAddress->getCountryCode());

        static::assertSame('DE-NW', $shippingAddress->getAdminArea1());
        static::assertSame('DE-NW', $billingAddress->getAdminArea1());
    }

    public function testConvertToValidationIssuesNoIssues(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        ['validationIssues' => $issues, 'status' => $status] = $transformer->convertToValidationIssues(
            new Cart(Uuid::randomHex()),
            new CartItemCollection(),
            $this->createMock(SalesChannelContext::class)
        );

        static::assertCount(0, $issues);
        static::assertSame(PayPalCart::VALIDATION_STATUS__VALID, $status);
    }

    public function testConvertToValidationIssues(): void
    {
        $validationIssueMock = $this->createMock(ValidationIssues::class);
        $validationIssueMock
            ->method('cartError')
            ->willReturnCallback(function (Error $error) {
                // Only blocking order errors should be added
                static::assertTrue($error->blockOrder());

                $issue = new ValidationIssue();
                $issue->setMessage($error::class);

                return $issue;
            });
        $validationIssueMock
            ->method('outOfStock')
            ->willReturnCallback(function (LineItem $lineItem) {
                $issue = new ValidationIssue();
                $issue->setItemId($lineItem->getReferencedId());
                $issue->setMessage('outOfStock');

                return $issue;
            });
        $validationIssueMock
            ->method('changedPrice')
            ->willReturnCallback(function (LineItem $lineItem) {
                $issue = new ValidationIssue();
                $issue->setItemId($lineItem->getReferencedId());
                $issue->setMessage('changedPrice');

                return $issue;
            });

        $localeRepository = $this->createMock(EntityRepository::class);
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $validationIssueMock,
            $localeRepository,
        );

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCurrency')
            ->willReturn(new CurrencyEntity());
        $context
            ->method('getContext')
            ->willReturn(Context::createDefaultContext());

        if (\method_exists($context, 'getLanguageInfo') && \class_exists(LanguageInfo::class)) {
            $context
                ->method('getLanguageInfo')
                ->willReturn(new LanguageInfo('Test', 'en-GB'));
        } else {
            $locale = new LocaleEntity();
            $locale->setId(Uuid::randomHex());
            $locale->setCode('en-GB');

            $localeRepository
                ->method('search')
                ->willReturn(new EntitySearchResult(
                    LocaleDefinition::ENTITY_NAME,
                    1,
                    new LocaleCollection([$locale]),
                    null,
                    new Criteria(),
                    Context::createDefaultContext()
                ));
        }

        $outOfStockId = Uuid::randomHex();
        $priceChangedId = Uuid::randomHex();
        $validItemId = Uuid::randomHex();
        $validItemWithInitPriceId = Uuid::randomHex();
        $validItemNoInitPriceId = Uuid::randomHex();

        $outOfStock = new LineItem($outOfStockId, 'product', $outOfStockId, 10);
        $outOfStock->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $outOfStock->setPayloadValue('stock', 5);

        $priceChanged = new LineItem($priceChangedId, 'product', $priceChangedId, 10);
        $priceChanged->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));

        $validItem = new LineItem($validItemId, 'product', $validItemId, 10);
        $validItem->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $validItem->setPayloadValue('stock', 50);

        $validItemWithInitPrice = new LineItem($validItemWithInitPriceId, 'product', $validItemWithInitPriceId, 10);
        $validItemWithInitPrice->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $validItemWithInitPrice->setPayloadValue('stock', 50);

        $validItemNoInitPrice = new LineItem($validItemNoInitPriceId, 'product', $validItemNoInitPriceId, 10);
        $validItemNoInitPrice->setPrice(new CalculatedPrice(100, 1000, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $validItemNoInitPrice->setPayloadValue('stock', 50);

        $invalidReferenceUuid = new LineItem(Uuid::randomHex(), 'product', 'some-string', 1);
        $referenceIdNull = new LineItem(Uuid::randomHex(), 'product', null, 1);

        $lineItem = new LineItem(Uuid::randomHex(), 'promotion');
        $lineItem->setLabel('Promotion Label');

        $cart = new Cart(Uuid::randomHex());
        $cart->addLineItems(new LineItemCollection([$outOfStock, $priceChanged, $validItem, $validItemWithInitPrice, $validItemNoInitPrice, $invalidReferenceUuid, $referenceIdNull]));
        $cart->addErrors(
            new ProductNotFoundError(Uuid::randomHex()),
            new PromotionCartAddedInformationError($lineItem),
            new PurchaseStepsError(Uuid::randomHex(), 'Name', 2),
        );

        $money1 = new Money();
        $money1->setValue('100');
        $cartItem1 = new CartItem();
        $cartItem1->setVariantId($validItemWithInitPriceId);
        $cartItem1->setPrice($money1);

        $money2 = new Money();
        $money2->setValue('50');
        $cartItem2 = new CartItem();
        $cartItem2->setVariantId($priceChangedId);
        $cartItem2->setPrice($money2);

        $cartItem3 = new CartItem();
        $cartItem3->setVariantId($validItemNoInitPriceId);

        $cartItems = new CartItemCollection([$cartItem1, $cartItem2, $cartItem3]);

        ['validationIssues' => $issues, 'status' => $status] = $transformer->convertToValidationIssues($cart, $cartItems, $context);

        static::assertCount(4, $issues);
        static::assertSame(PayPalCart::VALIDATION_STATUS__INVALID, $status);

        $productNotFoundIssue = $issues->get(0);
        $purchaseStepsIssue = $issues->get(1);
        $outOfStockIssue = $issues->get(2);
        $changedPriceIssue = $issues->get(3);

        static::assertInstanceOf(ValidationIssue::class, $productNotFoundIssue);
        static::assertInstanceOf(ValidationIssue::class, $purchaseStepsIssue);
        static::assertInstanceOf(ValidationIssue::class, $outOfStockIssue);
        static::assertInstanceOf(ValidationIssue::class, $changedPriceIssue);

        static::assertSame(ProductNotFoundError::class, $productNotFoundIssue->getMessage());
        static::assertSame(PurchaseStepsError::class, $purchaseStepsIssue->getMessage());
        static::assertSame('outOfStock', $outOfStockIssue->getMessage());
        static::assertSame($outOfStockId, $outOfStockIssue->getItemId());
        static::assertSame('changedPrice', $changedPriceIssue->getMessage());
        static::assertSame($priceChangedId, $changedPriceIssue->getItemId());
    }

    public function testConvertToAppliedCouponsNoCoupons(): void
    {
        $transformer = new PayPalCartTransformer(
            $this->createMock(EntityRepository::class),
            $this->createMock(EntityRepository::class),
            $this->createMock(AbstractShippingMethodRoute::class),
            $this->createMock(ValidationIssues::class),
            $this->createMock(EntityRepository::class),
        );

        $currency = new CurrencyEntity();
        $currency->setIsoCode('EUR');

        $context = $this->createMock(SalesChannelContext::class);
        $context
            ->method('getCurrency')
            ->willReturn($currency);

        $lineItem1 = new LineItem(Uuid::randomHex(), 'promotion');
        $lineItem1->setPrice(new CalculatedPrice(-10, -10, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem1->setPayloadValue('code', 'some-code');
        $lineItem1->setDescription('Code description');

        $lineItem2 = new LineItem(Uuid::randomHex(), 'promotion');
        $lineItem2->setPayloadValue('code', 'no-price-code');
        $lineItem2->setDescription('Code description');

        $coupons = $transformer->convertToAppliedCoupons([$lineItem1, $lineItem2], $context);
        static::assertInstanceOf(AppliedCouponCollection::class, $coupons);
        static::assertCount(1, $coupons);

        $coupon = $coupons->first();
        static::assertInstanceOf(AppliedCoupon::class, $coupon);
        static::assertSame('some-code', $coupon->getCode());
        static::assertSame('Code description', $coupon->getDescription());
        static::assertSame('-10', $coupon->getDiscountAmount()?->getValue());
        static::assertSame('EUR', $coupon->getDiscountAmount()->getCurrencyCode());
    }

    private function createCart(): Cart
    {
        $calculatedTaxes = new CalculatedTaxCollection();
        $calculatedTaxes->add(new CalculatedTax(19, 19, 100));
        $calculatedTaxes->add(new CalculatedTax(7, 7, 100));

        $cartPrice = new CartPrice(
            200,
            226,
            185,
            $calculatedTaxes,
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        );

        $delivery1 = new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(new \DateTime(), new \DateTime()),
            new ShippingMethodEntity(),
            new ShippingLocation(new CountryEntity(), null, null),
            new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection())
        );
        $delivery2 = new Delivery(
            new DeliveryPositionCollection(),
            new DeliveryDate(new \DateTime(), new \DateTime()),
            new ShippingMethodEntity(),
            new ShippingLocation(new CountryEntity(), null, null),
            new CalculatedPrice(5, 5, new CalculatedTaxCollection(), new TaxRuleCollection())
        );

        $deliveries = new DeliveryCollection();
        $deliveries->add($delivery1);
        $deliveries->add($delivery2);

        $lineItem1 = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItem1->setPrice(new CalculatedPrice(100, 100, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem2 = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItem2->setPrice(new CalculatedPrice(100, 100, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $promotion = new LineItem(Uuid::randomHex(), LineItem::PROMOTION_LINE_ITEM_TYPE);
        $promotion->setPrice(new CalculatedPrice(-15, -15, new CalculatedTaxCollection(), new TaxRuleCollection()));

        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice($cartPrice);
        $cart->setDeliveries($deliveries);
        $cart->setLineItems(new LineItemCollection([$lineItem1, $lineItem2, $promotion]));

        return $cart;
    }

    private static function createCustomer(?string $phoneNumber): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setFirstName('Max');
        $customer->setLastName('Mustermann');
        $customer->setEmail('mail@example.com');

        if ($phoneNumber) {
            $address = new CustomerAddressEntity();
            $address->setPhoneNumber($phoneNumber);
            $customer->setDefaultShippingAddress($address);
        }

        return $customer;
    }
}
