<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\OrdersApi\Builder\APM\AbstractAPMOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BancontactOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\BlikOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\EpsOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\GiropayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\IdealOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MultibancoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\MyBankOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\OxxoOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\P24OrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\SofortOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\APM\TrustlyOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\AbstractAPMPaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Bancontact;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Blik;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Eps;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Giropay;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Ideal;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Multibanco;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\MyBank;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Oxxo;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\P24;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Sofort;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Trustly;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\EventDispatcherMock;
use Swag\PayPal\Test\Mock\LoggerMock;
use Swag\PayPal\Util\PriceFormatter;

/**
 * @internal
 */
#[Package('checkout')]
class APMOrderBuilderTest extends TestCase
{
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    private const TEST_FIRST_NAME = 'FirstName';
    private const TEST_LAST_NAME = 'LastName';
    private const STATE_SHORT_CODE = 'NRW';
    private const ADDRESS_LINE_1 = 'Test address line 1';

    /**
     * @param class-string<AbstractAPMOrderBuilder> $orderBuilderClass
     * @param class-string<AbstractAPMPaymentSource> $structClass
     *
     * @dataProvider dataProviderAPM
     */
    public function testGetOrder(string $orderBuilderClass, array $requestData, string $structClass, array $expectedStructData): void
    {
        $orderBuilder = $this->createOrderBuilder($orderBuilderClass);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
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

        $order = $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer,
            new RequestDataBag($requestData)
        );

        static::assertSame(Order::PROCESSING_INSTRUCTION_COMPLETE_ON_APPROVAL, $order->getProcessingInstruction());
        $shipping = $order->getPurchaseUnits()->first()?->getShipping();
        static::assertSame(
            \sprintf('%s %s', self::TEST_FIRST_NAME, self::TEST_LAST_NAME),
            $shipping?->getName()->getFullName()
        );
        static::assertSame(self::ADDRESS_LINE_1, $shipping->getAddress()->getAddressLine2());
        static::assertSame(self::STATE_SHORT_CODE, $shipping->getAddress()->getAdminArea1());
        $paymentSource = $order->getPaymentSource();
        static::assertNotNull($paymentSource);
        $getter = 'get' . $this->getPropertyName($structClass);
        $struct = $paymentSource->$getter();
        static::assertInstanceOf($structClass, $struct);
        static::assertSame('DE', $struct->getCountryCode());
        static::assertSame('Some One', $struct->getName());
        $structArray = $struct->jsonSerialize();
        foreach ($expectedStructData as $key => $value) {
            static::assertSame($structArray[$key], $value);
        }
    }

    /**
     * @param class-string<AbstractAPMOrderBuilder> $orderBuilderClass
     *
     * @dataProvider dataProviderAPM
     */
    public function testGetOrderNoBillingAddress(string $orderBuilderClass, array $requestData): void
    {
        $orderBuilder = $this->createOrderBuilder($orderBuilderClass);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $paymentTransaction->getOrder()->assign(['billingAddress' => null]);

        $this->expectException(AddressNotFoundException::class);
        $this->expectExceptionMessageMatches('/Customer address with id "[a-z0-9]*" not found/');
        $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer,
            new RequestDataBag($requestData)
        );
    }

    /**
     * @param class-string<AbstractAPMOrderBuilder> $orderBuilderClass
     *
     * @dataProvider dataProviderAPM
     */
    public function testGetOrderNoShippingAddress(string $orderBuilderClass, array $requestData): void
    {
        $orderBuilder = $this->createOrderBuilder($orderBuilderClass);
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $customer->assign(['activeShippingAddress' => null, 'defaultShippingAddress' => null]);

        $this->expectException(AddressNotFoundException::class);
        $this->expectExceptionMessageMatches('/Customer address with id "[a-z0-9]*" not found/');
        $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer,
            new RequestDataBag($requestData)
        );
    }

    /**
     * @param class-string<AbstractAPMOrderBuilder> $orderBuilderClass
     *
     * @dataProvider dataProviderAPM
     */
    public function testGetOrderPrefix(string $orderBuilderClass, array $requestData): void
    {
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $settings = $this->createSystemConfigServiceMock([
            Settings::ORDER_NUMBER_PREFIX => 'foo',
            Settings::ORDER_NUMBER_SUFFIX => 'bar',
        ]);
        $order = $this->createOrderBuilder($orderBuilderClass, $settings)->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer,
            new RequestDataBag($requestData)
        );

        $invoiceId = $order->getPurchaseUnits()->first()?->getInvoiceId();
        static::assertIsString($invoiceId);
        static::assertStringStartsWith('foo', $invoiceId);
        static::assertStringEndsWith('bar', $invoiceId);
    }

    public function dataProviderAPM(): array
    {
        return [
            [BancontactOrderBuilder::class, [], Bancontact::class, []],
            [BlikOrderBuilder::class, [], Blik::class, ['email' => 'test@test.com']],
            // [Boletobancario::class, [], Boletobancario::class, ['email' => 'test@test.com']],
            [EpsOrderBuilder::class, [], Eps::class, []],
            [GiropayOrderBuilder::class, [], Giropay::class, []],
            [IdealOrderBuilder::class, [], Ideal::class, []],
            [MultibancoOrderBuilder::class, [], Multibanco::class, []],
            [MyBankOrderBuilder::class, [], MyBank::class, []],
            [OxxoOrderBuilder::class, [], Oxxo::class, ['email' => 'test@test.com']],
            [P24OrderBuilder::class, [], P24::class, ['email' => 'test@test.com']],
            [SofortOrderBuilder::class, [], Sofort::class, []],
            [TrustlyOrderBuilder::class, [], Trustly::class, []],
        ];
    }

    /**
     * @param class-string<AbstractAPMOrderBuilder> $orderBuilderClass
     */
    private function createOrderBuilder(string $orderBuilderClass, ?SystemConfigService $systemConfig = null): AbstractAPMOrderBuilder
    {
        $systemConfig = $systemConfig ?? $this->createDefaultSystemConfig();

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        return new $orderBuilderClass(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            new ItemListProvider($priceFormatter, new EventDispatcherMock(), new LoggerMock())
        );
    }

    private function getPropertyName(string $className): string
    {
        $path = \explode('\\', $className);

        return \array_pop($path);
    }
}
