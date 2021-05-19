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
use Shopware\Core\Framework\Test\TestCaseBase\BasicTestDataBehaviour;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;

class OrderFromOrderBuilderTest extends TestCase
{
    use BasicTestDataBehaviour;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    private const TEST_FIRST_NAME = 'FirstName';
    private const TEST_LAST_NAME = 'LastName';
    private const STATE_SHORT_CODE = 'NRW';
    private const ADDRESS_LINE_1 = 'Test address line 1';

    public function testGetOrderHasShippingAddressName(): void
    {
        $orderBuilder = $this->createOrderBuilder();
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
            $customer
        );

        $shipping = $order->getPurchaseUnits()[0]->getShipping();
        static::assertSame(
            \sprintf('%s %s', self::TEST_FIRST_NAME, self::TEST_LAST_NAME),
            $shipping->getName()->getFullName()
        );
        static::assertSame(self::ADDRESS_LINE_1, $shipping->getAddress()->getAddressLine2());
        static::assertSame(self::STATE_SHORT_CODE, $shipping->getAddress()->getAdminArea1());
    }

    public function testGetOrderNoBillingAddress(): void
    {
        $orderBuilder = $this->createOrderBuilder();
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $customer->assign(['activeBillingAddress' => null, 'defaultBillingAddress' => null]);

        $this->expectException(AddressNotFoundException::class);
        $this->expectExceptionMessageMatches('/Customer address with id "[a-z0-9]*" not found/');
        $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer
        );
    }

    public function testGetOrderNoShippingAddress(): void
    {
        $orderBuilder = $this->createOrderBuilder();
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
            $customer
        );
    }

    public function testGetOrderPrefix(): void
    {
        $paymentTransaction = $this->createPaymentTransactionStruct(ConstantsForTesting::VALID_ORDER_ID);
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $customer = $salesChannelContext->getCustomer();
        static::assertNotNull($customer);

        $settings = $this->createDefaultSettingStruct();
        $settings->setOrderNumberPrefix('foo');
        $orderBuilder = $this->createOrderBuilder($settings);

        $order = $orderBuilder->getOrder(
            $paymentTransaction,
            $salesChannelContext,
            $customer
        );

        static::assertStringStartsWith('foo', $order->getPurchaseUnits()[0]->getInvoiceId());
    }
}
