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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Swag\PayPal\Checkout\SPBCheckout\Service\SPBCheckoutDataService;
use Swag\PayPal\Payment\PayPalPaymentHandler;
use Swag\PayPal\PayPal\PaymentIntent;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Swag\PayPal\Test\Mock\PaymentMethodIdProviderMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;

class SPBCheckoutDataServiceTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var PaymentMethodIdProviderMock
     */
    private $paymentMethodIdProvider;

    public function setUp(): void
    {
        $this->paymentMethodIdProvider = new PaymentMethodIdProviderMock();
    }

    public function testGetCheckoutData(): void
    {
        $settings = $this->getDefaultSettings();

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $buttonData = $service->getCheckoutData($page);

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
        $buttonData = $service->getCheckoutData($page);

        static::assertNull($buttonData);
    }

    public function testGetCheckoutDataSpbDisabled(): void
    {
        $settings = $this->getDefaultSettings();
        $settings->setSpbCheckoutEnabled(false);

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $buttonData = $service->getCheckoutData($page);

        static::assertNull($buttonData);
    }

    public function testGetCheckoutDataEmptyCart(): void
    {
        $settings = $this->getDefaultSettings();

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $page->getCart()->setLineItems(new LineItemCollection());

        $buttonData = $service->getCheckoutData($page);

        static::assertNull($buttonData);
    }

    public function testGetCheckoutNoCustomer(): void
    {
        $settings = $this->getDefaultSettings();

        $service = $this->getService($settings);
        $page = $this->getCheckoutConfirmPage();
        $context = $page->getContext();
        $context->assign(['customer' => null]);

        $buttonData = $service->getCheckoutData($page);

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

        $context = Context::createDefaultContext();
        $salesChannelContext = Generator::createSalesChannelContext($context);
        $salesChannel = $salesChannelContext->getSalesChannel();
        $salesChannel->setId(Defaults::SALES_CHANNEL);

        $customer = $salesChannelContext->getCustomer();
        if ($customer) {
            $customer->setActive(true);
        }

        $currency = $salesChannelContext->getCurrency();
        $currency->setIsoCode('EUR');

        /** @var EntityRepositoryInterface $languageRepo */
        $languageRepo = $this->getContainer()->get('language.repository');
        $language = $languageRepo
            ->search(new Criteria([Defaults::LANGUAGE_SYSTEM]), Context::createDefaultContext())
            ->first();
        $salesChannel->setLanguage($language);

        $page = new CheckoutConfirmPage($salesChannelContext, $paymentMethods, $shippingMethods);
        $page->setCart(Generator::createCart());

        return $page;
    }

    private function getService(?SwagPayPalSettingGeneralStruct $settings): SPBCheckoutDataService
    {
        $settingsService = new SettingsServiceMock($settings);

        return new SPBCheckoutDataService($settingsService, $this->paymentMethodIdProvider);
    }
}
