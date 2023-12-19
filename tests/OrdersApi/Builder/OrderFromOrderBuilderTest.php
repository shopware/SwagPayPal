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
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\Exception\MissingPayloadException;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\OrderFromOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Settings;
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
class OrderFromOrderBuilderTest extends TestCase
{
    private const TEST_FIRST_NAME = 'FirstName';
    private const TEST_LAST_NAME = 'LastName';
    private const STATE_SHORT_CODE = 'NRW';
    private const ADDRESS_LINE_1 = 'Test address line 1';

    private OrderFromOrderBuilder $orderBuilder;

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

        $this->orderBuilder = new OrderFromOrderBuilder(
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
            new RequestDataBag(),
            $salesChannelContext,
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
            new RequestDataBag(),
            $salesChannelContext,
        );
    }

    public function testGetOrderNoShippingAddress(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $paymentTransaction->getOrder()->getDeliveries()?->first()?->assign(['shippingOrderAddress' => null]);
        $customer->assign(['activeShippingAddress' => null, 'defaultShippingAddress' => null]);

        $this->expectException(MissingPayloadException::class);
        $this->expectExceptionMessage('Missing request payload purchaseUnit.shipping to order "created" not found');
        $this->orderBuilder->getOrder(
            $paymentTransaction,
            new RequestDataBag(),
            $salesChannelContext,
        );
    }

    public function testGetOrderPrefix(): void
    {
        $paymentTransaction = new SyncPaymentTransactionStruct($this->createOrderTransaction(), $this->createOrder());
        $salesChannelContext = $this->createSalesChannelContext();

        $this->systemConfig->set(Settings::ORDER_NUMBER_PREFIX, 'foo');
        $this->systemConfig->set(Settings::ORDER_NUMBER_SUFFIX, 'bar');
        $order = $this->orderBuilder->getOrder(
            $paymentTransaction,
            new RequestDataBag(),
            $salesChannelContext,
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
            new RequestDataBag(),
            $salesChannelContext,
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
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]),
            $salesChannelContext,
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
            new RequestDataBag([VaultTokenService::REQUEST_CREATE_VAULT => true]),
            $salesChannelContext,
        );

        static::assertSame('testToken', $order->getPaymentSource()?->getPaypal()?->getVaultId());
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

        return $salesChannelContext;
    }
}
