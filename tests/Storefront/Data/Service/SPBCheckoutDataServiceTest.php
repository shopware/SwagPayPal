<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Data\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\SalesChannel\CustomerVaultTokenRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\RestApi\V1\Resource\TokenResource;
use Swag\PayPal\Setting\Service\CredentialsUtilInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\Data\Service\SPBCheckoutDataService;
use Swag\PayPal\Test\Helper\CartTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PayPalMethodData;
use Swag\PayPal\Util\LocaleCodeProvider;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SPBCheckoutDataService::class)]
class SPBCheckoutDataServiceTest extends TestCase
{
    use CartTrait;
    use ServicesTrait;

    protected SPBCheckoutDataService $checkoutDataService;

    protected SystemConfigServiceMock $systemConfigService;

    protected CustomerVaultTokenRoute&MockObject $customerVaultTokenRoute;

    protected PaymentMethodDataRegistry&MockObject $paymentMethodDataRegistry;

    protected function setUp(): void
    {
        $this->systemConfigService = $this->createSystemConfigServiceMock([
            Settings::VAULTING_ENABLED_WALLET => false,
        ]);

        $this->customerVaultTokenRoute = $this->createMock(CustomerVaultTokenRoute::class);
        $this->paymentMethodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);

        $container = $this->createMock(Container::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $service): mixed {
                return match ($service) {
                    SystemConfigService::class => $this->systemConfigService,
                    default => static::fail('Getting service "' . $service . '" is not handled'),
                };
            });

        $this->paymentMethodDataRegistry
            ->expects($this->once())
            ->method('getPaymentMethod')
            ->with(PayPalMethodData::class)
            ->willReturn(new PayPalMethodData($container));

        $this->checkoutDataService = new SPBCheckoutDataService(
            $this->paymentMethodDataRegistry,
            $this->createMock(LocaleCodeProvider::class),
            $this->createMock(RouterInterface::class),
            $this->systemConfigService,
            $this->createMock(CredentialsUtilInterface::class),
            $this->createMock(TokenResource::class),
            $this->customerVaultTokenRoute,
        );
    }

    #[DataProvider('providerTestSetUserIdToken')]
    public function testSetUserIdToken(?string $expected, bool $guest, bool $enabled): void
    {
        $this->systemConfigService->set(Settings::VAULTING_ENABLED_WALLET, $enabled);
        $context = Generator::generateSalesChannelContext(currency: $this->createCurrency(), customer: $this->createCustomer($guest));
        $this->customerVaultTokenRoute
            ->expects($this->exactly((int) (!$guest && $enabled)))
            ->method('getVaultToken')
            ->willReturn(new TokenResponse('user-id-token'));

        $data = $this->checkoutDataService->buildCheckoutData(
            $context,
            $this->createCart(Uuid::randomHex()),
        );

        static::assertSame($expected, $data?->getUserIdToken());
    }

    public function testSetAppSwitchEnabled(): void
    {
        $context = Generator::generateSalesChannelContext(currency: $this->createCurrency(), customer: $this->createCustomer(false));

        $data = $this->checkoutDataService->buildCheckoutData(
            $context,
            $this->createCart(Uuid::randomHex()),
        );

        static::assertFalse($data?->getAppSwitchEnabled());

        $this->systemConfigService->set(Settings::SPB_APP_SWITCH_ENABLED, true);
        $data = $this->checkoutDataService->buildCheckoutData(
            $context,
            $this->createCart(Uuid::randomHex()),
        );

        static::assertTrue($data?->getAppSwitchEnabled());
    }

    public static function providerTestSetUserIdToken(): \Generator
    {
        yield 'non-guest, setting enabled' => ['user-id-token', false, true];
        yield 'guest, setting enabled' => [null, true, true];
        yield 'non-guest, setting disabled' => [null, false, false];
        yield 'guest, setting disabled' => [null, true, false];
    }

    private function createCustomer(bool $guest): CustomerEntity
    {
        $customer = new CustomerEntity();
        $customer->setId(Uuid::randomHex());
        $customer->setGuest($guest);
        $customer->setActiveBillingAddress($this->createCustomerAddress());

        return $customer;
    }

    private function createCurrency(): CurrencyEntity
    {
        $currency = new CurrencyEntity();
        $currency->setId(Uuid::randomHex());
        $currency->setIsoCode('EUR');

        return $currency;
    }

    private function createCustomerAddress(): CustomerAddressEntity
    {
        $country = new CountryEntity();
        $country->setIso3('DEU');
        $country->setIso('DE');

        $countryState = new CountryStateEntity();
        $countryState->setName('Test Country State');

        $address = new CustomerAddressEntity();
        $address->setStreet('Test street');
        $address->setCountryState($countryState);
        $address->setCountry($country);
        $address->setFirstName('Max');
        $address->setLastName('Mustermann');
        $address->setCity('Testhausen');
        $address->setZipcode('44444');

        return $address;
    }
}
