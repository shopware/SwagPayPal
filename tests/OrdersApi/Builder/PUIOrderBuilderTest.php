<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Builder;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\PUI\Exception\MissingBirthdayException;
use Swag\PayPal\Checkout\PUI\Exception\MissingPhoneNumberException;
use Swag\PayPal\OrdersApi\Builder\PUIOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
class PUIOrderBuilderTest extends TestCase
{
    use IntegrationTestBehaviour;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;

    private const TEST_FIRST_NAME = 'FirstName';
    private const TEST_LAST_NAME = 'LastName';
    private const STATE_SHORT_CODE = 'NRW';
    private const ADDRESS_LINE_1 = 'Test address line 1';

    public function testGetOrder(): void
    {
        $orderBuilder = $this->createPUIOrderBuilder();
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $countryState = new CountryStateEntity();
        $countryState->setShortCode(self::STATE_SHORT_CODE);

        $order = $orderBuilder->getOrder(
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
        $paymentSource = $order->getPaymentSource();
        static::assertNotNull($paymentSource);
        $payUponInvoice = $paymentSource->getPayUponInvoice();
        static::assertNotNull($payUponInvoice);
        static::assertSame((new \DateTime('-30 years'))->format('Y-m-d'), $payUponInvoice->getBirthDate());
        static::assertSame('41', $payUponInvoice->getPhone()->getCountryCode());
        static::assertSame('01234956789', $payUponInvoice->getPhone()->getNationalNumber());
    }

    public function testGetOrderNoBillingAddress(): void
    {
        $orderBuilder = $this->createPUIOrderBuilder();
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
            new RequestDataBag(),
        );
    }

    public function testGetOrderNoBirthday(): void
    {
        $orderBuilder = $this->createPUIOrderBuilder();
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $customer->assign(['birthday' => null]);

        $this->expectException(MissingBirthdayException::class);
        $this->expectExceptionMessageMatches('/Birthday is required for PUI for customer "[a-z0-9]*"/');
        $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );
    }

    public function testGetOrderNoPhoneNumber(): void
    {
        $orderBuilder = $this->createPUIOrderBuilder();
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $billingAddress = $paymentTransaction->getOrder()->getBillingAddress();
        static::assertNotNull($billingAddress);
        $billingAddress->assign(['phoneNumber' => null]);

        $this->expectException(MissingPhoneNumberException::class);
        $this->expectExceptionMessageMatches('/Phone Number is required for PUI for order address "[a-z0-9]*"/');
        $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );
    }

    public function testGetOrderPrefix(): void
    {
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $settings = SystemConfigServiceMock::createWithoutCredentials();
        $settings->set(Settings::ORDER_NUMBER_PREFIX, 'foo');
        $settings->set(Settings::ORDER_NUMBER_SUFFIX, 'bar');
        $order = $this->createPUIOrderBuilder($settings)->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            new RequestDataBag(),
        );

        $invoiceId = $order->getPurchaseUnits()->first()?->getInvoiceId();
        static::assertIsString($invoiceId);
        static::assertStringStartsWith('foo', $invoiceId);
        static::assertStringEndsWith('bar', $invoiceId);
    }

    private function createPUIOrderBuilder(?SystemConfigService $systemConfig = null): PUIOrderBuilder
    {
        $systemConfig = $systemConfig ?? SystemConfigServiceMock::createWithCredentials();

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();

        return new PUIOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->getContainer()->get(LocaleCodeProvider::class),
            new ItemListProvider($priceFormatter, $this->createMock(EventDispatcherInterface::class), new NullLogger()),
        );
    }
}
