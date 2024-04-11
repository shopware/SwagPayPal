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
use Shopware\Commercial\Subscription\Checkout\Cart\Recurring\SubscriptionRecurringDataStruct;
use Shopware\Commercial\Subscription\Entity\Subscription\SubscriptionEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Payment\Cart\Recurring\RecurringDataStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Common\ExperienceContext;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\PaymentsApi\Builder\OrderPaymentBuilderTest;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PayPalOrderBuilderTest extends TestCase
{
    use CartTrait;

    private const TEST_FIRST_NAME = 'FirstName';
    private const TEST_LAST_NAME = 'LastName';
    private const STATE_SHORT_CODE = 'NRW';
    private const ADDRESS_LINE_1 = 'Test address line 1';

    private PayPalOrderBuilder $orderBuilder;

    private SystemConfigServiceMock $systemConfig;

    private VaultTokenService&MockObject $vaultTokenService;

    protected function setUp(): void
    {
        $this->systemConfig = SystemConfigServiceMock::createWithCredentials();
        $this->vaultTokenService = $this->createMock(VaultTokenService::class);

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        $this->orderBuilder = new PayPalOrderBuilder(
            $this->systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $this->systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            new ItemListProvider($priceFormatter, $this->createMock(EventDispatcherInterface::class), new NullLogger()),
            $this->vaultTokenService,
        );
    }

    public function testGetOrderHasShippingAddressName(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $countryState = new CountryStateEntity();
        $countryState->setShortCode(self::STATE_SHORT_CODE);

        $shippingAddress = new CustomerAddressEntity();
        $shippingAddress->setFirstName(self::TEST_FIRST_NAME);
        $shippingAddress->setLastName(self::TEST_LAST_NAME);
        $shippingAddress->setAdditionalAddressLine1(self::ADDRESS_LINE_1);
        $shippingAddress->setCountryState($countryState);
        $shippingAddress->setStreet('Test street 123');
        $shippingAddress->setCity('Test City');
        $shippingAddress->setZipcode('12345');
        $customer->setActiveShippingAddress($shippingAddress);

        $order = $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );

        $shipping = $order->getPurchaseUnits()->first()?->getShipping();
        static::assertSame(
            \sprintf('%s %s', self::TEST_FIRST_NAME, self::TEST_LAST_NAME),
            $shipping?->getName()->getFullName()
        );
        static::assertSame(self::ADDRESS_LINE_1, $shipping->getAddress()->getAddressLine2());
        static::assertSame(self::STATE_SHORT_CODE, $shipping->getAddress()->getAdminArea1());
    }

    public function testGetOrderNoBillingAddress(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $paymentTransaction->getOrder()->assign(['billingAddress' => null]);
        $customer->assign(['activeBillingAddress' => null, 'defaultBillingAddress' => null]);

        $this->expectException(OrderException::class);
        $this->expectExceptionMessage('The required association "billingAddress" is missing .');
        $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );
    }

    public function testGetOrderNoShippingAddress(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();
        $paymentTransaction->getOrder()->getDeliveries()?->clear();

        $order = $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );

        static::assertSame(ExperienceContext::SHIPPING_PREFERENCE_NO_SHIPPING, $order->getPaymentSource()?->getPaypal()?->getExperienceContext()?->getShippingPreference());
    }

    public function testGetOrderPrefix(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();

        $this->systemConfig->set(Settings::ORDER_NUMBER_PREFIX, 'foo');
        $this->systemConfig->set(Settings::ORDER_NUMBER_SUFFIX, 'bar');
        $order = $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );

        $invoiceId = $order->getPurchaseUnits()->first()?->getInvoiceId();
        static::assertIsString($invoiceId);
        static::assertStringStartsWith('foo', $invoiceId);
        static::assertStringEndsWith('bar', $invoiceId);
    }

    public function testGetOrderRequestsVaultingWithSubscription(): void
    {
        if (!\class_exists(SubscriptionRecurringDataStruct::class) || !\class_exists(SubscriptionEntity::class)) {
            static::markTestSkipped('Commercial is not installed');
        }

        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('getSubscription')->willReturn(new SubscriptionEntity());
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );
    }

    public function testGetOrderRequestsVaultingWithUserRequest(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn(null);
        $this->vaultTokenService->expects(static::once())->method('requestVaulting');

        $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );
    }

    public function testGetOrderUsesVaultTokenIfExists(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder(), new RecurringDataStruct(Uuid::randomHex(), new \DateTime()));
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setToken('testToken');

        $this->vaultTokenService->expects(static::once())->method('getAvailableToken')->willReturn($vaultToken);

        $order = $this->orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]),
        );

        static::assertSame('testToken', $order->getPaymentSource()?->getPaypal()?->getVaultId());
    }

    public function testGetOrderWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The transaction with id  is invalid or could not be found.');
        $this->orderBuilder->getOrderFromCart($this->createCart('', false), $salesChannelContext, new RequestDataBag());
    }

    public function testGetOrderInvalidIntent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "intent" is missing or invalid');
        $this->systemConfig->set(Settings::INTENT, 'invalidIntent');
        $this->orderBuilder->getOrderFromCart($this->createCart(''), $salesChannelContext, new RequestDataBag());
    }

    public function testGetOrderInvalidLandingPageType(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $this->expectException(PayPalSettingsInvalidException::class);
        $this->expectExceptionMessage('Required setting "landingPage" is missing or invalid');
        $this->systemConfig->set(Settings::LANDING_PAGE, 'invalidLandingPageType');
        $this->orderBuilder->getOrderFromCart($this->createCart(''), $salesChannelContext, new RequestDataBag());
    }

    public function testGetOrderWithDisabledSubmitCartConfig(): void
    {
        $cart = $this->createCart('');
        $salesChannelContext = $this->createSalesChannelContext();

        $this->systemConfig->set(Settings::SUBMIT_CART, false);
        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $purchaseUnit = $order->getPurchaseUnits()->first();
        static::assertNotNull($purchaseUnit);
        static::assertNull($purchaseUnit->getAmount()->getBreakdown());
    }

    public function testGetOrderWithMismatchingAmount(): void
    {
        $cart = $this->createCartWithLineItem(new CalculatedPrice(5.0, 5.95, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $salesChannelContext = $this->createSalesChannelContext();

        $this->systemConfig->set(Settings::SUBMIT_CART, false);
        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
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
        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());

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

        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());

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

        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
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

        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
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

        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $breakdown = $order->getPurchaseUnits()->first()?->getAmount()->getBreakdown();
        static::assertNotNull($breakdown);
        $taxTotal = $breakdown->getTaxTotal();
        static::assertNotNull($taxTotal);

        static::assertSame((string) $productTax, $taxTotal->getValue());
    }

    private function createOrderTransaction(?string $transactionId = null): OrderTransactionEntity
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setOrderId(OrderPaymentBuilderTest::TEST_ORDER_ID);

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

    private function createOrder(): OrderEntity
    {
        $orderNumber = OrderPaymentBuilderTest::TEST_ORDER_NUMBER_WITHOUT_PREFIX;
        $order = new OrderEntity();
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $order->setShippingCosts(new CalculatedPrice(4.99, 4.99, new CalculatedTaxCollection(), new TaxRuleCollection()));
        $order->setId(Uuid::randomHex());
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
        $state->setShortCode('NRW');
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
        $address->setFirstName('FirstName');
        $address->setLastName('LastName');
        $address->setStreet('Street 1');
        $address->setAdditionalAddressLine1('Test address line 1');
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

    private function createSalesChannelContext(): SalesChannelContext
    {
        $salesChannelContext = Generator::createSalesChannelContext();
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
