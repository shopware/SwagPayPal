<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Installment\Banner;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\Installment\Banner\InstallmentBannerSubscriber;
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

class InstallmentBannerSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use PaymentMethodTrait;

    private const CART_TOTAL_PRICE = 123.45;
    private const PRODUCT_PRICE = 678.9;
    private const ADVANCED_PRODUCT_PRICE = 111.22;

    /**
     * @var PaymentMethodUtil
     */
    private $paymentMethodUtil;

    /**
     * @var string
     */
    private $payPalPaymentMethodId;

    /**
     * @var Context
     */
    private $context;

    protected function setUp(): void
    {
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->paymentMethodUtil = $paymentMethodUtil;
        $this->context = Context::createDefaultContext();
        $this->payPalPaymentMethodId = (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($this->context);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = InstallmentBannerSubscriber::getSubscribedEvents();

        static::assertCount(7, $events);

        static::assertSame('addInstallmentBanner', $events[CheckoutCartPageLoadedEvent::class]);
        static::assertSame('addInstallmentBanner', $events[CheckoutConfirmPageLoadedEvent::class]);
        static::assertSame('addInstallmentBanner', $events[CheckoutRegisterPageLoadedEvent::class]);
        static::assertSame('addInstallmentBanner', $events[OffcanvasCartPageLoadedEvent::class]);
        static::assertSame('addInstallmentBanner', $events[ProductPageLoadedEvent::class]);

        static::assertSame('addInstallmentBannerPagelet', $events[FooterPageletLoadedEvent::class]);
        static::assertSame('addInstallmentBannerPagelet', $events[QuickviewPageletLoadedEvent::class]);
    }

    public function testAddInstallmentBannerPayPalNotInSalesChannel(): void
    {
        $event = $this->createCheckoutCartPageLoadedEvent(false);

        $this->createInstallmentBannerSubscriber()->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerInvalidSettings(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $event = $this->createCheckoutCartPageLoadedEvent();

        $this->createInstallmentBannerSubscriber($settings)->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerDisabled(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');
        $settings->setInstallmentBannerEnabled(false);
        $event = $this->createCheckoutCartPageLoadedEvent();

        $this->createInstallmentBannerSubscriber($settings)->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerCheckoutCart(): void
    {
        $event = $this->createCheckoutCartPageLoadedEvent();

        $this->createInstallmentBannerSubscriber()->addInstallmentBanner($event);

        $page = $event->getPage();
        $extensions = $page->getExtensions();
        static::assertCount(2, $extensions);

        /** @var BannerData|null $checkoutCartBannerData */
        $checkoutCartBannerData = $page->getExtension(
            InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_CART_PAGE_EXTENSION_ID
        );
        static::assertInstanceOf(BannerData::class, $checkoutCartBannerData);
        static::assertSame(self::CART_TOTAL_PRICE, $checkoutCartBannerData->getAmount());
        static::assertSame('flex', $checkoutCartBannerData->getLayout());
        static::assertSame('grey', $checkoutCartBannerData->getColor());

        $this->assertBannerData($page, self::CART_TOTAL_PRICE);
    }

    public function testAddInstallmentBannerProductPage(): void
    {
        $event = $this->createProductPageLoadedEvent();

        $this->createInstallmentBannerSubscriber()->addInstallmentBanner($event);

        $page = $event->getPage();
        $extensions = $page->getExtensions();
        static::assertCount(1, $extensions);

        $this->assertBannerData($page, self::PRODUCT_PRICE);
    }

    public function testAddInstallmentBannerProductPageWithAdvancedPrices(): void
    {
        $event = $this->createProductPageLoadedEvent(true);

        $this->createInstallmentBannerSubscriber()->addInstallmentBanner($event);

        $page = $event->getPage();
        $extensions = $page->getExtensions();
        static::assertCount(1, $extensions);

        $this->assertBannerData($page, self::ADVANCED_PRODUCT_PRICE);
    }

    public function testAddInstallmentBannerFooterPayPalNotInSalesChannel(): void
    {
        $event = $this->createFooterPageletLoadedEvent(false);

        $this->createInstallmentBannerSubscriber()->addInstallmentBannerPagelet($event);

        static::assertEmpty($event->getPagelet()->getExtensions());
    }

    public function testAddInstallmentBannerFooterInvalidSettings(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $event = $this->createFooterPageletLoadedEvent();

        $this->createInstallmentBannerSubscriber($settings)->addInstallmentBannerPagelet($event);

        static::assertEmpty($event->getPagelet()->getExtensions());
    }

    public function testAddInstallmentBannerFooterDisabled(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('testClientId');
        $settings->setClientSecret('testClientSecret');
        $settings->setInstallmentBannerEnabled(false);
        $event = $this->createFooterPageletLoadedEvent();

        $this->createInstallmentBannerSubscriber($settings)->addInstallmentBannerPagelet($event);

        static::assertEmpty($event->getPagelet()->getExtensions());
    }

    public function testAddInstallmentBannerFooterPagelet(): void
    {
        $event = $this->createFooterPageletLoadedEvent();

        $this->createInstallmentBannerSubscriber()->addInstallmentBannerPagelet($event);

        $pagelet = $event->getPagelet();
        $extensions = $pagelet->getExtensions();
        static::assertCount(1, $extensions);

        $this->assertBannerData($pagelet, 0);
    }

    private function assertBannerData(Struct $page, float $price): void
    {
        /** @var BannerData|null $bannerData */
        $bannerData = $page->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);
        static::assertInstanceOf(BannerData::class, $bannerData);
        static::assertSame($price, $bannerData->getAmount());
        static::assertSame('text', $bannerData->getLayout());
        static::assertSame('blue', $bannerData->getColor());
        static::assertSame('8x1', $bannerData->getRatio());
        static::assertSame('primary', $bannerData->getLogoType());
        static::assertSame('black', $bannerData->getTextColor());
    }

    private function createInstallmentBannerSubscriber(
        ?SwagPayPalSettingStruct $settings = null
    ): InstallmentBannerSubscriber {
        if ($settings === null) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('testClientId');
            $settings->setClientSecret('testClientSecret');
        }

        return new InstallmentBannerSubscriber(
            new SettingsServiceMock($settings),
            $this->paymentMethodUtil,
            new BannerDataService($this->paymentMethodUtil)
        );
    }

    private function createCheckoutCartPageLoadedEvent(bool $withPayPalInContext = true): CheckoutCartPageLoadedEvent
    {
        return new CheckoutCartPageLoadedEvent(
            $this->createCheckoutCartPage(),
            $this->createSalesChannelContext($withPayPalInContext),
            $this->createRequest()
        );
    }

    private function createCheckoutCartPage(): CheckoutCartPage
    {
        $page = new CheckoutCartPage();
        $cart = new Cart('test', 'testToken');
        $cart->setPrice(
            new CartPrice(
                0,
                self::CART_TOTAL_PRICE,
                0,
                new CalculatedTaxCollection(),
                new TaxRuleCollection(),
                CartPrice::TAX_STATE_GROSS
            )
        );
        $page->setCart($cart);

        return $page;
    }

    private function createProductPageLoadedEvent(bool $withAdvancedPrices = false): ProductPageLoadedEvent
    {
        return new ProductPageLoadedEvent(
            $this->createProductPage($withAdvancedPrices),
            $this->createSalesChannelContext(),
            $this->createRequest()
        );
    }

    private function createProductPage(bool $withAdvancedPrices = false): ProductPage
    {
        $page = new ProductPage();
        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice(
            new CalculatedPrice(
                self::PRODUCT_PRICE,
                self::PRODUCT_PRICE,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            )
        );
        $calculatedPrices = [];
        if ($withAdvancedPrices) {
            $calculatedPrices[] = new CalculatedPrice(
                self::ADVANCED_PRODUCT_PRICE,
                self::ADVANCED_PRODUCT_PRICE,
                new CalculatedTaxCollection(),
                new TaxRuleCollection()
            );
        }
        $product->setCalculatedPrices(new PriceCollection($calculatedPrices));

        $page->setProduct($product);

        return $page;
    }

    private function createFooterPageletLoadedEvent(bool $withPayPalInContext = true): FooterPageletLoadedEvent
    {
        return new FooterPageletLoadedEvent(
            new FooterPagelet(null),
            $this->createSalesChannelContext($withPayPalInContext),
            $this->createRequest()
        );
    }

    private function createSalesChannelContext(bool $withPayPalInContext = true): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);
        $salesChannelContext = $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL
        );

        $paymentMethodsArray = [$salesChannelContext->getPaymentMethod()];
        if ($withPayPalInContext) {
            $payPalPaymentMethod = new PaymentMethodEntity();
            $payPalPaymentMethod->setId($this->payPalPaymentMethodId);
            $paymentMethodsArray[] = $payPalPaymentMethod;
            $this->addPayPalToDefaultsSalesChannel($this->payPalPaymentMethodId, $this->context);
        }

        $salesChannelContext->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection($paymentMethodsArray));

        return $salesChannelContext;
    }

    private function createRequest(): Request
    {
        return new Request();
    }
}
