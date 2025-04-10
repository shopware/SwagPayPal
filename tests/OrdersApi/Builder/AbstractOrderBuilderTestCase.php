<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Transaction\Struct\Transaction;
use Shopware\Core\Checkout\Cart\Transaction\Struct\TransactionCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\AbstractPaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
abstract class AbstractOrderBuilderTestCase extends TestCase
{
    protected const TEST_FIRST_NAME = 'FirstName';
    protected const TEST_LAST_NAME = 'LastName';
    protected const STATE_SHORT_CODE = 'NRW';
    protected const ADDRESS_LINE_1 = 'Test address line 1';

    protected SystemConfigServiceMock $systemConfig;

    protected PurchaseUnitProvider $purchaseUnitProvider;

    protected LocaleCodeProvider&MockObject $localeCodeProvider;

    protected ItemListProvider $itemListProvider;

    protected function setUp(): void
    {
        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $customIdProvider = new CustomIdProviderMock();

        $addressProvider = new AddressProvider();
        $this->localeCodeProvider = $this->createMock(LocaleCodeProvider::class);
        $this->systemConfig = SystemConfigServiceMock::createWithCredentials();
        $this->purchaseUnitProvider = new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $this->systemConfig);
        $this->itemListProvider = new ItemListProvider($priceFormatter, $this->createMock(EventDispatcherInterface::class), new NullLogger());
    }

    public function testGetOrderHasShippingAddressName(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        $shipping = $order->getPurchaseUnits()->first()?->getShipping();
        static::assertSame(
            \sprintf('%s %s', self::TEST_FIRST_NAME, self::TEST_LAST_NAME),
            $shipping?->getName()->getFullName()
        );
        static::assertSame(self::ADDRESS_LINE_1, $shipping->getAddress()->getAddressLine2());
        static::assertSame(self::STATE_SHORT_CODE, $shipping->getAddress()->getAdminArea1());
    }

    public function testGetOrderNoShippingAddress(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $order->getDeliveries()?->clear();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        static::assertSame(ExperienceContext::SHIPPING_PREFERENCE_NO_SHIPPING, $order->getPaymentSource()?->first($this->getPaymentSourceClass())?->getExperienceContext()?->getShippingPreference());
    }

    public function testGetOrderPrefix(): void
    {
        $orderTransaction = $this->createOrderTransaction();
        $order = $this->createOrder();
        $paymentTransaction = new PaymentTransactionStruct($orderTransaction->getId());

        $this->systemConfig->set(Settings::ORDER_NUMBER_PREFIX, 'foo');
        $this->systemConfig->set(Settings::ORDER_NUMBER_SUFFIX, 'bar');
        $order = $this->getBuilder()->getOrder(
            $paymentTransaction,
            $orderTransaction,
            $order,
            Context::createDefaultContext(),
            new Request(),
        );

        $invoiceId = $order->getPurchaseUnits()->first()?->getInvoiceId();
        static::assertIsString($invoiceId);
        static::assertStringStartsWith('foo', $invoiceId);
        static::assertStringEndsWith('bar', $invoiceId);
    }

    public function testGetOrderWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->getBuilder()->getOrderFromCart($this->createCart('', false), $salesChannelContext, new RequestDataBag());
    }

    public function testGetOrderInvalidIntent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "intent" is missing or invalid');
        $this->systemConfig->set(Settings::INTENT, 'invalidIntent');
        $this->getBuilder()->getOrderFromCart($this->createCart(''), $salesChannelContext, new RequestDataBag());
    }

    public function testGetOrderInvalidLandingPageType(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "landingPage" is missing or invalid');
        $this->systemConfig->set(Settings::LANDING_PAGE, 'invalidLandingPageType');
        $this->getBuilder()->getOrderFromCart($this->createCart(''), $salesChannelContext, new RequestDataBag());
    }

    public function testGetOrderWithDisabledSubmitCartConfig(): void
    {
        $cart = $this->createCart('');
        $salesChannelContext = $this->createSalesChannelContext();

        $this->systemConfig->set(Settings::SUBMIT_CART, false);
        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $purchaseUnit = $order->getPurchaseUnits()->first();
        static::assertNotNull($purchaseUnit);
        static::assertNull($purchaseUnit->getAmount()->getBreakdown());
    }

    public function testGetOrderWithMismatchingAmount(): void
    {
        $cart = $this->createCartWithLineItem(new CalculatedPrice(5.0, 5.95, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $salesChannelContext = $this->createSalesChannelContext();

        $this->systemConfig->set(Settings::SUBMIT_CART, false);
        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $purchaseUnit = $order->getPurchaseUnits()->first();
        static::assertNotNull($purchaseUnit);
        static::assertNull($purchaseUnit->getAmount()->getBreakdown());
        static::assertNull($purchaseUnit->getItems());
    }

    public function testGetOrderWithProductWithZeroPrice(): void
    {
        $cart = $this->createCartWithLineItem(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $cart->setPrice($this->createCartPrice(0.0, 0.0, 0.0));
        $salesChannelContext = $this->createSalesChannelContext();
        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());

        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        static::assertSame('0.00', $paypalOrderItems[0]->getUnitAmount()->getValue());
    }

    public function testGetOrderWithNegativePriceLineItemHasCorrectItemArray(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('', true, 9.84, 9.84);
        $discount = new CalculatedPrice(-2.5, -2.5, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $cart->add($this->createLineItem($discount, LineItem::PROMOTION_LINE_ITEM_TYPE));
        $cart->add($this->createLineItem($productPrice));

        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());

        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        static::assertSame(0, \array_keys($paypalOrderItems)[0], 'First array key of the PayPal items array must be 0.');
    }

    public function testLineItemLabelTooLongIsTruncated(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('', true, 12.34, 12.34);
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam volu';
        $cartLineItem = $this->createLineItem($productPrice);
        $cartLineItem->setLabel($productName);
        $cart->add($cartLineItem);

        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        $expectedItemName = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magn';
        static::assertSame($expectedItemName, $paypalOrderItems[0]->getName());
    }

    public function testLineItemProductNumberTooLongIsTruncated(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $cart = $this->createCart('', true, 12.34, 12.34);
        $productPrice = new CalculatedPrice(12.34, 12.34, new CalculatedTaxCollection(), new TaxRuleCollection());
        $productNumber = 'SW-100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        $cartLineItem = $this->createLineItem($productPrice);
        $cartLineItem->setPayloadValue('productNumber', $productNumber);
        $cart->add($cartLineItem);

        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $paypalOrderItems = $order->getPurchaseUnits()->first()?->getItems()?->getElements();
        static::assertNotNull($paypalOrderItems);
        static::assertNotEmpty($paypalOrderItems);
        $expectedItemSku = 'SW-1000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000';
        static::assertSame($expectedItemSku, $paypalOrderItems[0]->getSku());
    }

    public function testGetOrderFromNetCart(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $productNetPrice = 168.07;
        $productTax = 31.93;
        $taxRate = 19.0;

        $cart = $this->createCart('', true, $productNetPrice, $productNetPrice + $productTax);
        $cart->add($this->createLineItem(new CalculatedPrice($productNetPrice, $productNetPrice, new CalculatedTaxCollection([19 => new CalculatedTax($productTax, 19, $productNetPrice)]), new TaxRuleCollection())));
        $cartPrice = new CartPrice(
            $productNetPrice,
            $productNetPrice + $productTax,
            $productNetPrice,
            new CalculatedTaxCollection([new CalculatedTax($productTax, $taxRate, $productNetPrice)]),
            new TaxRuleCollection([new TaxRule($taxRate)]),
            CartPrice::TAX_STATE_NET
        );
        $cart->setPrice($cartPrice);
        $firstCartTransaction = $cart->getTransactions()->first();
        static::assertNotNull($firstCartTransaction);
        $firstCartTransaction->setAmount(
            new CalculatedPrice(
                $productNetPrice,
                $productNetPrice + $productTax,
                new CalculatedTaxCollection([new CalculatedTax($productTax, $taxRate, $productNetPrice)]),
                new TaxRuleCollection([new TaxRule($taxRate)])
            )
        );

        $order = $this->getBuilder()->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $breakdown = $order->getPurchaseUnits()->first()?->getAmount()->getBreakdown();
        static::assertNotNull($breakdown);
        $taxTotal = $breakdown->getTaxTotal();
        static::assertNotNull($taxTotal);

        static::assertSame((string) $productTax, $taxTotal->getValue());
    }

    abstract protected function getBuilder(): AbstractOrderBuilder;

    /**
     * @return class-string<AbstractPaymentSource>
     */
    abstract protected function getPaymentSourceClass(): string;

    protected function createOrderTransaction(?string $transactionId = null): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setOrderId(ConstantsForTesting::TEST_ORDER_ID);

        if ($transactionId === null) {
            $transactionId = Uuid::randomHex();
        }
        $orderTransaction->setId($transactionId);

        $amount = new CalculatedPrice(
            722.69,
            860.0,
            new CalculatedTaxCollection([
                new CalculatedTax(
                    137.31,
                    19.0,
                    722.69
                ),
            ]),
            new TaxRuleCollection([
                new TaxRule(
                    19.0,
                    100.0
                ),
            ]),
            1
        );
        $orderTransaction->setAmount($amount);

        return $orderTransaction;
    }

    protected function createOrder(): OrderEntity
    {
        $orderNumber = ConstantsForTesting::TEST_ORDER_NUMBER_WITHOUT_PREFIX;
        $order = new OrderEntity();
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setShippingCosts(new CalculatedPrice(4.99, 4.99, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $order->setId(Uuid::randomHex());
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $order->setSalesChannel($salesChannel);
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('EUR');
        $order->setCurrency($currency);
        $order->setOrderNumber($orderNumber);
        $order->setPrice(new CartPrice(
            722.69,
            860.0,
            722.69,
            new CalculatedTaxCollection([
                new CalculatedTax(
                    137.31,
                    19.0,
                    722.6890756302521
                ),
            ]),
            new TaxRuleCollection([
                new TaxRule(
                    19.0,
                    100.0
                ),
            ]),
            CartPrice::TAX_STATE_NET
        ));
        $order->setAmountNet(722.69);
        $order->setAmountTotal(860.0);
        $order->setTaxStatus(CartPrice::TAX_STATE_GROSS);
        $lineItem = new OrderLineItemEntity();
        $lineItem->setId(Uuid::randomHex());
        $lineItem->setType('product');
        $lineItem->setIdentifier('test');
        $lineItem->setQuantity(1);
        $lineItem->setLabel('test');
        $lineItem->setUnitPrice(5.0);
        $lineItem->setTotalPrice(5.0);
        $lineItem->setPrice(new CalculatedPrice(10, 10, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $lineItem->setPriceDefinition(new QuantityPriceDefinition(10, new TaxRuleCollection()));
        $lineItem->setGood(true);
        $order->setLineItems(new OrderLineItemCollection([$lineItem]));

        $country = new CountryEntity();
        $country->setIso('DE');
        $state = new CountryStateEntity();
        $state->setShortCode(self::STATE_SHORT_CODE);
        $address = new OrderAddressEntity();
        $address->setFirstName('Some');
        $address->setLastName('One');
        $address->setStreet('Street 1');
        $address->setZipcode('12345');
        $address->setCity('City');
        $address->setPhoneNumber('+41 (0123) 49567-89'); // extra weird for filter testing
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);
        $address->setCountryState($state);
        $order->setBillingAddress($address);
        $order->setBillingAddressId($address->getId());

        $delivery = new OrderDeliveryEntity();
        $delivery->setId(Uuid::randomHex());
        $address = new OrderAddressEntity();
        $address->setFirstName(self::TEST_FIRST_NAME);
        $address->setLastName(self::TEST_LAST_NAME);
        $address->setStreet('Street 1');
        $address->setAdditionalAddressLine1(self::ADDRESS_LINE_1);
        $address->setZipcode('12345');
        $address->setCity('City');
        $address->setPhoneNumber('+41 (0123) 49567-89'); // extra weird for filter testing
        $address->setId(Uuid::randomHex());
        $address->setCountry($country);
        $address->setCountryState($state);
        $delivery->setShippingOrderAddress($address);
        $order->setDeliveries(new OrderDeliveryCollection([$delivery]));

        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setFirstName('Test');
        $orderCustomer->setLastName('Customer');
        $orderCustomer->setEmail('test@test.com');
        $order->setOrderCustomer($orderCustomer);

        return $order;
    }

    private function createCart(string $paymentMethodId, bool $withTransaction = true, float $netPrice = 9.0, float $totalPrice = 10.9): Cart
    {
        $cart = new Cart(Uuid::randomHex());
        if ($withTransaction) {
            $transaction = new Transaction(
                new CalculatedPrice(
                    $totalPrice,
                    $totalPrice,
                    new CalculatedTaxCollection(),
                    new TaxRuleCollection()
                ),
                $paymentMethodId
            );
            $cart->setTransactions(new TransactionCollection([$transaction]));
        }

        $cart->setPrice($this->createCartPrice($netPrice, $totalPrice, $netPrice));

        return $cart;
    }

    private function createCartPrice(float $netPrice, float $totalPrice, float $positionPrice): CartPrice
    {
        return new CartPrice(
            $netPrice,
            $totalPrice,
            $positionPrice,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        );
    }

    private function createLineItem(
        ?CalculatedPrice $lineItemPrice,
        string $lineItemType = LineItem::PRODUCT_LINE_ITEM_TYPE,
    ): LineItem {
        $lineItem = new LineItem(Uuid::randomHex(), $lineItemType);
        $lineItem->setLabel('Test product');
        if ($lineItemPrice !== null) {
            $lineItem->setPrice($lineItemPrice);
        } else {
            $lineItem->setPrice(new CalculatedPrice(10.9, 10.9, new CalculatedTaxCollection(), new TaxRuleCollection()));
        }

        return $lineItem;
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->getCurrency()->setIsoCode('EUR');
        $salesChannelContext->getCustomer()?->setEmail('test@example.com');
        $salesChannelContext->getCustomer()?->setFirstName('Test');
        $salesChannelContext->getCustomer()?->setLastName('Customer');

        $address = new CustomerAddressEntity();
        $address->setFirstName('FirstName');
        $address->setLastName('LastName');
        $address->setStreet('Street 1');
        $address->setAdditionalAddressLine1('Test address line 1');
        $address->setZipcode('12345');
        $address->setCity('City');
        $address->setPhoneNumber('+41 (0123) 49567-89'); // extra weird for filter testing
        $address->setId(Uuid::randomHex());
        $salesChannelContext->getCustomer()?->setActiveBillingAddress($address);
        $salesChannelContext->getCustomer()?->setActiveShippingAddress($address);

        return $salesChannelContext;
    }

    private function createCartWithLineItem(?CalculatedPrice $lineItemPrice = null): Cart
    {
        $cart = $this->createCart('', true, $lineItemPrice ? $lineItemPrice->getTotalPrice() : 9.0, $lineItemPrice ? $lineItemPrice->getTotalPrice() : 10.9);
        $cart->add($this->createLineItem($lineItemPrice));

        return $cart;
    }
}
