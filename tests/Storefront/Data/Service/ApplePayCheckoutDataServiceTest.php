<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data\Service;

use Monolog\Test\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\ApplePayCheckoutDataService;
use Swag\PayPal\Storefront\Data\Struct\ApplePayCheckoutData;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\Lifecycle\Method\ApplePayMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ApplePayCheckoutDataServiceTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    protected ApplePayCheckoutDataService $checkoutDataService;

    protected SystemConfigServiceMock $systemConfigService;

    protected SalesChannelContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = Generator::createSalesChannelContext(
            null,
            null,
            null,
            $this->createCurrencyEntity(),
            null,
            null,
            null,
            null,
            null,
            null,
            $this->createCustomer(),
        );

        $this->systemConfigService = $this->createSystemConfigServiceMock([
            Settings::BRAND_NAME => 'Test Name',
        ]);

        $this->checkoutDataService = new ApplePayCheckoutDataService(
            $this->createMock(PaymentMethodDataRegistry::class),
            $this->createMock(LocaleCodeProvider::class),
            $this->createMock(RouterInterface::class),
            $this->systemConfigService,
            $this->createMock(CredentialsUtilInterface::class)
        );
    }

    public function testMethodDataClass(): void
    {
        static::assertSame(ApplePayMethodData::class, $this->checkoutDataService->getMethodDataClass());
    }

    public function testCheckoutDataServiceWithCart(): void
    {
        $checkoutDataMock = $this->checkoutDataService->buildCheckoutData(
            $this->context,
            $this->createCart()
        );

        static::assertInstanceOf(ApplePayCheckoutData::class, $checkoutDataMock);
        static::assertEquals(20.00, $checkoutDataMock->getTotalPrice());
        static::assertSame('Test Name', $checkoutDataMock->getBrandName());
        static::assertArrayHasKey('addressLines', $checkoutDataMock->getBillingAddress());
        static::assertSame('Test street', $checkoutDataMock->getBillingAddress()['addressLines']);
        static::assertArrayHasKey('administrativeArea', $checkoutDataMock->getBillingAddress());
        static::assertSame('Test Country State', $checkoutDataMock->getBillingAddress()['administrativeArea']);
        static::assertArrayHasKey('country', $checkoutDataMock->getBillingAddress());
        static::assertSame('DEU', $checkoutDataMock->getBillingAddress()['country']);
        static::assertArrayHasKey('countryCode', $checkoutDataMock->getBillingAddress());
        static::assertSame('DE', $checkoutDataMock->getBillingAddress()['countryCode']);
        static::assertArrayHasKey('familyName', $checkoutDataMock->getBillingAddress());
        static::assertSame('Mustermann', $checkoutDataMock->getBillingAddress()['familyName']);
        static::assertArrayHasKey('givenName', $checkoutDataMock->getBillingAddress());
        static::assertSame('Max', $checkoutDataMock->getBillingAddress()['givenName']);
        static::assertArrayHasKey('locality', $checkoutDataMock->getBillingAddress());
        static::assertSame('Testhausen', $checkoutDataMock->getBillingAddress()['locality']);
        static::assertArrayHasKey('postalCode', $checkoutDataMock->getBillingAddress());
        static::assertSame('44444', $checkoutDataMock->getBillingAddress()['postalCode']);
    }

    public function testCheckoutDataServiceWithOrder(): void
    {
        $checkoutDataMock = $this->checkoutDataService->buildCheckoutData(
            $this->context,
            null,
            $this->createOrderEntity(),
        );

        static::assertInstanceOf(ApplePayCheckoutData::class, $checkoutDataMock);
        static::assertEquals(20.00, $checkoutDataMock->getTotalPrice());
        static::assertSame('Test Name', $checkoutDataMock->getBrandName());
        static::assertArrayHasKey('addressLines', $checkoutDataMock->getBillingAddress());
        static::assertSame('Test Street', $checkoutDataMock->getBillingAddress()['addressLines']);
        static::assertArrayHasKey('administrativeArea', $checkoutDataMock->getBillingAddress());
        static::assertSame('Test Country State', $checkoutDataMock->getBillingAddress()['administrativeArea']);
        static::assertArrayHasKey('country', $checkoutDataMock->getBillingAddress());
        static::assertSame('DEU', $checkoutDataMock->getBillingAddress()['country']);
        static::assertArrayHasKey('countryCode', $checkoutDataMock->getBillingAddress());
        static::assertSame('DE', $checkoutDataMock->getBillingAddress()['countryCode']);
        static::assertArrayHasKey('familyName', $checkoutDataMock->getBillingAddress());
        static::assertSame('Mustermann', $checkoutDataMock->getBillingAddress()['familyName']);
        static::assertArrayHasKey('givenName', $checkoutDataMock->getBillingAddress());
        static::assertSame('Max', $checkoutDataMock->getBillingAddress()['givenName']);
        static::assertArrayHasKey('locality', $checkoutDataMock->getBillingAddress());
        static::assertSame('Testhausen', $checkoutDataMock->getBillingAddress()['locality']);
        static::assertArrayHasKey('postalCode', $checkoutDataMock->getBillingAddress());
        static::assertSame('44444', $checkoutDataMock->getBillingAddress()['postalCode']);
    }

    private function createCurrencyEntity(): CurrencyEntity
    {
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('EUR');

        return $currency;
    }

    private function createOrderEntity(): OrderEntity
    {
        $order = new OrderEntity();

        $order->setId(Uuid::randomHex());
        $order->setBillingAddress($this->createBillingAddress());
        $order->setPrice($this->createCartPrice(20.0, 20.0, 20.0));

        return $order;
    }

    private function createBillingAddress(): OrderAddressEntity
    {
        $billingAddress = new OrderAddressEntity();
        $billingAddress->setStreet('Test Street');

        $countryState = new CountryStateEntity();
        $countryState->setName('Test Country State');
        $billingAddress->setCountryState($countryState);

        $country = new CountryEntity();
        $country->setIso3('DEU');
        $country->setIso('DE');
        $billingAddress->setCountry($country);

        $billingAddress->setLastName('Mustermann');
        $billingAddress->setFirstName('Max');
        $billingAddress->setCity('Testhausen');
        $billingAddress->setZipcode('44444');

        return $billingAddress;
    }

    private function createCart(): Cart
    {
        $cart = new Cart(Uuid::randomHex());
        $cart->setPrice($this->createCartPrice(20.0, 20.0, 20.0));

        return $cart;
    }

    private function createCustomerAddressEntity(): CustomerAddressEntity
    {
        $activeBillingAddress = new CustomerAddressEntity();

        $activeBillingAddress->setStreet('Test street');

        $countryState = new CountryStateEntity();
        $countryState->setName('Test Country State');
        $activeBillingAddress->setCountryState($countryState);

        $country = new CountryEntity();
        $country->setIso3('DEU');
        $country->setIso('DE');
        $activeBillingAddress->setCountry($country);

        $activeBillingAddress->setFirstName('Max');
        $activeBillingAddress->setLastName('Mustermann');
        $activeBillingAddress->setCity('Testhausen');
        $activeBillingAddress->setZipcode('44444');

        return $activeBillingAddress;
    }

    private function createCustomer(): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setActiveBillingAddress($this->createCustomerAddressEntity());

        return $customer;
    }
}
