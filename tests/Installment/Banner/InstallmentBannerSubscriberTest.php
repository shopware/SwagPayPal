<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Installment\Banner;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Struct\Struct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Pagelet\Footer\FooterPagelet;
use Shopware\Storefront\Pagelet\Footer\FooterPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Installment\Banner\BannerData;
use Swag\PayPal\Installment\Banner\InstallmentBannerSubscriber;
use Swag\PayPal\Installment\Banner\Service\BannerDataService;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\Compatibility\Generator;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class InstallmentBannerSubscriberTest extends TestCase
{
    use ServicesTrait;
    use PaymentMethodTrait;

    private const CART_TOTAL_PRICE = 123.45;
    private const PRODUCT_PRICE = 678.9;
    private const ADVANCED_PRODUCT_PRICE = 111.22;

    private PaymentMethodUtil $paymentMethodUtil;

    private string $payPalPaymentMethodId;

    private Context $context;

    /**
     * @var MockObject&ExcludedProductValidator
     */
    private $excludedProductValidator;

    protected function setUp(): void
    {
        $this->paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $this->context = Context::createDefaultContext();
        $this->payPalPaymentMethodId = (string) $this->paymentMethodUtil->getPayPalPaymentMethodId($this->context);
        $this->excludedProductValidator = $this->createMock(ExcludedProductValidator::class);
        $this->paymentMethodUtil->reset();
    }

    protected function tearDown(): void
    {
        $this->removePaymentMethodFromDefaultsSalesChannel($this->payPalPaymentMethodId);
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
        $event = $this->createCheckoutCartPageLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::CLIENT_ID => null,
            Settings::CLIENT_SECRET => null,
        ])->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerCheckoutCartDisabled(): void
    {
        $event = $this->createCheckoutCartPageLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::INSTALLMENT_BANNER_CART_ENABLED => false,
        ])->addInstallmentBanner($event);

        /** @var BannerData $bannerData */
        $bannerData = $event->getPage()->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);

        static::assertFalse($bannerData->getCartEnabled());
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

    public function testAddInstallmentBannerCheckoutCartExcludedProduct(): void
    {
        $event = $this->createCheckoutCartPageLoadedEvent();
        $this->excludedProductValidator->method('cartContainsExcludedProduct')->willReturn(true);

        $this->createInstallmentBannerSubscriber()->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
    }

    public function testAddInstallmentBannerOffCanvasCartDisabled(): void
    {
        $event = $this->createOffCanvasCartPageLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::INSTALLMENT_BANNER_OFF_CANVAS_CART_ENABLED => false,
        ])->addInstallmentBanner($event);

        /** @var BannerData $bannerData */
        $bannerData = $event->getPage()->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);

        static::assertFalse($bannerData->getOffCanvasCartEnabled());
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

    public function testAddInstallmentBannerProductPageDisabled(): void
    {
        $event = $this->createProductPageLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::INSTALLMENT_BANNER_DETAIL_PAGE_ENABLED => false,
        ])->addInstallmentBanner($event);

        /** @var BannerData $bannerData */
        $bannerData = $event->getPage()->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);

        static::assertFalse($bannerData->getDetailPageEnabled());
    }

    public function testAddInstallmentBannerProductPageExcludedProduct(): void
    {
        $event = $this->createProductPageLoadedEvent();
        $this->excludedProductValidator->method('isProductExcluded')->willReturn(true);

        $this->createInstallmentBannerSubscriber()->addInstallmentBanner($event);

        static::assertEmpty($event->getPage()->getExtensions());
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
        $event = $this->createFooterPageletLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::CLIENT_ID => null,
            Settings::CLIENT_SECRET => null,
        ])->addInstallmentBannerPagelet($event);

        static::assertEmpty($event->getPagelet()->getExtensions());
    }

    public function testAddInstallmentBannerFooterDisabled(): void
    {
        $event = $this->createFooterPageletLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::INSTALLMENT_BANNER_FOOTER_ENABLED => false,
        ])->addInstallmentBannerPagelet($event);

        /** @var BannerData $bannerData */
        $bannerData = $event->getPagelet()->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);

        static::assertFalse($bannerData->getFooterEnabled());
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

    public function testAddInstallmentBannerAccountLoginPageDisabled(): void
    {
        $event = $this->createCheckoutRegisterPageLoadedEvent();

        $this->createInstallmentBannerSubscriber([
            Settings::INSTALLMENT_BANNER_LOGIN_PAGE_ENABLED => false,
        ])->addInstallmentBanner($event);

        /** @var BannerData $bannerData */
        $bannerData = $event->getPage()->getExtension(InstallmentBannerSubscriber::PAYPAL_INSTALLMENT_BANNER_DATA_EXTENSION_ID);

        static::assertFalse($bannerData->getLoginPageEnabled());
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
        static::assertTrue($bannerData->getFooterEnabled());
        static::assertTrue($bannerData->getCartEnabled());
        static::assertTrue($bannerData->getOffCanvasCartEnabled());
        static::assertTrue($bannerData->getLoginPageEnabled());
        static::assertTrue($bannerData->getDetailPageEnabled());
    }

    private function createInstallmentBannerSubscriber(array $settings = []): InstallmentBannerSubscriber
    {
        $settings = $this->createSystemConfigServiceMock(\array_merge(
            [
                Settings::CLIENT_ID => 'testClientId',
                Settings::CLIENT_SECRET => 'testClientSecret',
            ],
            $settings
        ));

        return new InstallmentBannerSubscriber(
            new SettingsValidationService($settings, new NullLogger()),
            $this->paymentMethodUtil,
            new BannerDataService(
                $this->paymentMethodUtil,
                new CredentialsUtil($settings),
                $settings,
                $this->createMock(EntityRepository::class),
            ),
            $this->excludedProductValidator,
            new NullLogger(),
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
        $cart = Generator::createCart('testToken');
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

    private function createOffCanvasCartPageLoadedEvent(bool $withPayPalInContext = true): OffcanvasCartPageLoadedEvent
    {
        return new OffcanvasCartPageLoadedEvent(
            $this->createOffCanvasCartPage(),
            $this->createSalesChannelContext($withPayPalInContext),
            $this->createRequest()
        );
    }

    private function createOffCanvasCartPage(): OffcanvasCartPage
    {
        $page = new OffcanvasCartPage();
        $cart = new Cart('testName', 'testToken');
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

    private function createCheckoutRegisterPageLoadedEvent(bool $withPayPalInContext = true): CheckoutRegisterPageLoadedEvent
    {
        return new CheckoutRegisterPageLoadedEvent(
            $this->createCheckoutRegisterPage(),
            $this->createSalesChannelContext($withPayPalInContext),
            $this->createRequest()
        );
    }

    private function createCheckoutRegisterPage(): CheckoutRegisterPage
    {
        $page = new CheckoutRegisterPage();
        $cart = new Cart('testName', 'testToken');
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
        if (!$withPayPalInContext) {
            $this->removePaymentMethodFromDefaultsSalesChannel($this->payPalPaymentMethodId);
        }

        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('payment_method.repository');

        $criteria = (new Criteria())
            ->setLimit(1)
            ->addFilter(new EqualsFilter('active', true))
            ->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
                new EqualsFilter('handlerIdentifier', PayPalPaymentHandler::class),
            ]));

        $otherPaymentMethodId = $repository->searchIds($criteria, Context::createDefaultContext())->firstId();
        static::assertNotNull($otherPaymentMethodId);

        $paymentMethodsArray = [['id' => $otherPaymentMethodId]];
        if ($withPayPalInContext) {
            $paymentMethodsArray[] = ['id' => $this->payPalPaymentMethodId];
        }

        /** @var EntityRepository $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');
        $salesChannelRepo->update([
            [
                'id' => TestDefaults::SALES_CHANNEL,
                'paymentMethodId' => $otherPaymentMethodId,
                'paymentMethods' => $paymentMethodsArray,
            ],
        ], Context::createDefaultContext());

        return $this->getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL
        );
    }

    private function createRequest(): Request
    {
        return new Request();
    }
}
