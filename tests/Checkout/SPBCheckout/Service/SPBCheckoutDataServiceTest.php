<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\SPBCheckout\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Swag\PayPal\Test\Mock\PaymentMethodIdProviderMock;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Util\LocaleCodeProviderMock;

class SPBCheckoutDataServiceTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var PaymentMethodIdProviderMock
     */
    private $paymentMethodIdProvider;

    /**
     * @var SalesChannelContext
     */
    private $salesChannelContext;

    public function setUp(): void
    {
        $this->paymentMethodIdProvider = new PaymentMethodIdProviderMock();
        $context = Context::createDefaultContext();
        $this->salesChannelContext = Generator::createSalesChannelContext($context);
    }

    public function testGetCheckoutData(): void
    {
        $settings = $this->getDefaultSettings();

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $buttonData = $service->getCheckoutData($page, $this->salesChannelContext);

        static::assertNotNull($buttonData);
        if ($buttonData === null) {
            return;
        }

        static::assertFalse($buttonData->getUseSandbox());
        static::assertSame('foo', $buttonData->getClientId());
        static::assertSame('en_GB', $buttonData->getLanguageIso());
        static::assertSame('EUR', $buttonData->getCurrency());
        static::assertSame(PaymentIntent::SALE, $buttonData->getIntent());

        $paymentMethodId = $this->paymentMethodIdProvider->getPayPalPaymentMethodId(Context::createDefaultContext());
        static::assertSame($paymentMethodId, $buttonData->getPaymentMethodId());
    }

    public function testEmptySettings(): void
    {
        $service = $this->getService(null);
        $page = $this->getCheckoutConfirmPage();
        $buttonData = $service->getCheckoutData($page, $this->salesChannelContext);

        static::assertNull($buttonData);
    }

    public function testGetCheckoutDataSpbDisabled(): void
    {
        $settings = $this->getDefaultSettings();
        $settings->setSpbCheckoutEnabled(false);

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $buttonData = $service->getCheckoutData($page, $this->salesChannelContext);

        static::assertNull($buttonData);
    }

    public function testGetCheckoutDataEmptyCart(): void
    {
        $settings = $this->getDefaultSettings();

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $page->getCart()->setLineItems(new LineItemCollection());

        $buttonData = $service->getCheckoutData($page, $this->salesChannelContext);

        static::assertNull($buttonData);
    }

    public function testGetCheckoutNoCustomer(): void
    {
        $settings = $this->getDefaultSettings();

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $this->salesChannelContext->assign(['customer' => null]);

        $buttonData = $service->getCheckoutData($page, $this->salesChannelContext);

        static::assertNull($buttonData);
    }

    private function getDefaultSettings(): SwagPayPalSettingGeneralStruct
    {
        $settings = new SwagPayPalSettingGeneralStruct();
        $settings->assign([
            'spbCheckoutEnabled' => true,
            'clientId' => 'foo',
            'clientSecret' => 'bar',
            'sandbox' => false,
            'intent' => PaymentIntent::SALE,
        ]);

        return $settings;
    }

    private function getCheckoutConfirmPage(): CheckoutConfirmPage
    {
        $payPalPaymentMethodEntity = new PaymentMethodEntity();
        $payPalPaymentMethodEntity->assign([
            'id' => $this->paymentMethodIdProvider->getPayPalPaymentMethodId(Context::createDefaultContext()),
            'handlerIdentifier' => PayPalPaymentHandler::class,
            'name' => 'PayPal',
            'description' => 'Bezahlung per PayPal - einfach, schnell und sicher.',
        ]);
        $paymentMethods = new PaymentMethodCollection([$payPalPaymentMethodEntity]);
        $shippingMethods = new ShippingMethodCollection();

        $salesChannel = $this->salesChannelContext->getSalesChannel();
        $salesChannel->setId(Defaults::SALES_CHANNEL);

        $customer = $this->salesChannelContext->getCustomer();
        if ($customer) {
            $customer->setActive(true);
        }

        $currency = $this->salesChannelContext->getCurrency();
        $currency->setIsoCode('EUR');

        $page = new CheckoutConfirmPage($paymentMethods, $shippingMethods);
        $page->setCart(Generator::createCart());

        return $page;
    }

    private function getService(?SwagPayPalSettingGeneralStruct $settings): SPBCheckoutDataService
    {
        $settingsService = new SettingsServiceMock($settings);
        $localeCodeProviderMock = new LocaleCodeProviderMock(new LanguageRepoMock());

        return new SPBCheckoutDataService($settingsService, $this->paymentMethodIdProvider, $localeCodeProviderMock);
    }
}
