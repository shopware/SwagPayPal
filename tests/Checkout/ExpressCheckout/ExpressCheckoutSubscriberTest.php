<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPage;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Pagelet\Wishlist\GuestWishlistPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoader;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Service\CredentialsUtil;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCheckoutSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    private CartService $cartService;

    protected function setUp(): void
    {
        $this->cartService = $this->getContainer()->get(CartService::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = ExpressCheckoutSubscriber::getSubscribedEvents();
        $expectedEvents = [
            CheckoutCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            NavigationPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            OffcanvasCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            SearchPageLoadedEvent::class => 'addExpressCheckoutDataToPage',

            'sales_channel.product.search.result.loaded' => 'addExcludedProductsToSearchResult',

            QuickviewPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',
            GuestWishlistPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',

            SwitchBuyBoxVariantEvent::class => 'addExpressCheckoutDataToBuyBoxSwitch',

            'framework.validation.address.create' => 'disableAddressValidation',
            'framework.validation.customer.create' => 'disableCustomerValidation',

            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmLoaded',

            CustomerEvents::MAPPING_REGISTER_CUSTOMER => 'addPayerIdToCustomer',
        ];

        static::assertSame($expectedEvents, $subscribedEvents);
    }

    public function testAddExpressCheckoutDataToPageNavigationPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new NavigationPageLoadedEvent(
            new NavigationPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageProductPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new ProductPageLoadedEvent(
            new ProductPage(),
            $salesChannelContext,
            new Request()
        );
        $event->getPage()->setProduct(new SalesChannelProductEntity());
        $event->getPage()->getProduct()->setId(Uuid::randomHex());

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageProductPageWithExcludedProduct(): void
    {
        $productId = Uuid::randomHex();
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new ProductPageLoadedEvent(
            new ProductPage(),
            $salesChannelContext,
            new Request()
        );
        $event->getPage()->setProduct(new SalesChannelProductEntity());
        $event->getPage()->getProduct()->setId($productId);

        $this->getExpressCheckoutSubscriber(true, false, false, $productId)->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageOffCanvasCartPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex());
        $event = new OffcanvasCartPageLoadedEvent(
            new OffcanvasCartPage(),
            $salesChannelContext,
            new Request()
        );
        $event->getPage()->setCart($this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext));

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageCheckoutRegisterPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex());
        $event = new CheckoutRegisterPageLoadedEvent(
            new CheckoutRegisterPage(),
            $salesChannelContext,
            new Request()
        );
        $event->getPage()->setCart($this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext));

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageCheckoutCartPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex());
        $event = new CheckoutCartPageLoadedEvent(
            new CheckoutCartPage(),
            $salesChannelContext,
            new Request()
        );
        $event->getPage()->setCart($this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext));

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageCheckoutCartPageWithExcludedProduct(): void
    {
        $excludedProductId = Uuid::randomHex();
        $salesChannelContext = $this->createSalesChannelContext($excludedProductId);
        $event = new CheckoutCartPageLoadedEvent(
            new CheckoutCartPage(),
            $salesChannelContext,
            new Request()
        );
        $event->getPage()->setCart($this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext));

        $this->getExpressCheckoutSubscriber(true, false, false, $excludedProductId)->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageCartPageWithZeroValue(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex());
        $event = new CheckoutCartPageLoadedEvent(new CheckoutCartPage(), $salesChannelContext, new Request());

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $cart->setPrice(new CartPrice(
            0.0,
            0.0,
            0,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            CartPrice::TAX_STATE_GROSS
        ));

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageWithoutPayPalInSalesChannel(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setId(Uuid::randomHex());
        $salesChannelContext->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection());
        $event = new CheckoutRegisterPageLoadedEvent(
            new CheckoutRegisterPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageWithInactivePaymentMethod(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex(), false);
        $event = new CheckoutCartPageLoadedEvent(
            new CheckoutCartPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageWithInvalidSettings(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new CheckoutRegisterPageLoadedEvent(
            new CheckoutRegisterPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber(false)->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageNavigationPageLoadedEventWithEcsListingDisabled(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new NavigationPageLoadedEvent(
            new NavigationPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber(true, true)->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageWithoutCart(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $this->cartService->createNew($salesChannelContext->getToken());
        $event = new OffcanvasCartPageLoadedEvent(
            new OffcanvasCartPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToBuyBoxSwitchEvent(): void
    {
        $event = $this->createBuyBoxSwitchEvent();

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToBuyBoxSwitch($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getProduct()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToBuyBoxSwitchWithInactivePaymentMethod(): void
    {
        $event = $this->createBuyBoxSwitchEvent(false);

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToBuyBoxSwitch($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getProduct()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToBuyBoxSwitchWithoutPayPalInSalesChannel(): void
    {
        $event = $this->createBuyBoxSwitchEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setId(Uuid::randomHex());
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection());

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToBuyBoxSwitch($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getProduct()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageletQuickviewPageletLoadedEvent(): void
    {
        $event = $this->createQuickviewPageletLoadedEvent();

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPagelet($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPagelet()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageletWithInactivePaymentMethod(): void
    {
        $event = $this->createQuickviewPageletLoadedEvent(false);

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPagelet($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPagelet()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageletWithoutPayPalInSalesChannel(): void
    {
        $event = $this->createQuickviewPageletLoadedEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setId(Uuid::randomHex());
        $event->getSalesChannelContext()->getSalesChannel()->setPaymentMethods(new PaymentMethodCollection());

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPagelet($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPagelet()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageletWithInvalidSettings(): void
    {
        $event = $this->createQuickviewPageletLoadedEvent();

        $this->getExpressCheckoutSubscriber(false)->addExpressCheckoutDataToPagelet($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPagelet()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageletQuickviewPageletLoadedEventWithEcsDetailDisabled(): void
    {
        $event = $this->createQuickviewPageletLoadedEvent();

        $this->getExpressCheckoutSubscriber(true, false, true)->addExpressCheckoutDataToPagelet($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPagelet()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testRemovingOtherPaymentMethodsOnExpressCheckout(): void
    {
        $event = $this->createCheckoutConfirmPageLoadedEvent(true, true);

        $this->getExpressCheckoutSubscriber()->onCheckoutConfirmLoaded($event);

        $paymentMethods = $event->getPage()->getPaymentMethods();
        static::assertCount(1, $paymentMethods);
        $firstPaymentMethod = $paymentMethods->first();
        static::assertNotNull($firstPaymentMethod);
        static::assertSame(PayPalPaymentHandler::class, $firstPaymentMethod->getHandlerIdentifier());
    }

    public function testNotRemovingOtherPaymentMethodsOnCheckoutConfirm(): void
    {
        $event = $this->createCheckoutConfirmPageLoadedEvent(false);

        $this->getExpressCheckoutSubscriber()->onCheckoutConfirmLoaded($event);

        $paymentMethods = $event->getPage()->getPaymentMethods();
        // Nothing should happen, so PayPal + 3 others
        static::assertCount(4, $paymentMethods);
    }

    public function testNotRemovingOtherPaymentMethodsOnCheckoutConfirmIfPayPalNotAvailable(): void
    {
        $event = $this->createCheckoutConfirmPageLoadedEvent(true, false);

        $this->getExpressCheckoutSubscriber()->onCheckoutConfirmLoaded($event);

        $paymentMethods = $event->getPage()->getPaymentMethods();
        // PayPal not active, so only 3 others
        static::assertCount(3, $paymentMethods);
    }

    private function assertExpressCheckoutButtonData(
        ExpressCheckoutButtonData $expectedExpressCheckoutButtonData,
        ?ExpressCheckoutButtonData $actualExpressCheckoutButtonData
    ): void {
        static::assertNotNull($actualExpressCheckoutButtonData);
        static::assertSame($expectedExpressCheckoutButtonData->getProductDetailEnabled(), $actualExpressCheckoutButtonData->getProductDetailEnabled());
        static::assertSame($expectedExpressCheckoutButtonData->getOffCanvasEnabled(), $actualExpressCheckoutButtonData->getOffCanvasEnabled());
        static::assertSame($expectedExpressCheckoutButtonData->getLoginEnabled(), $actualExpressCheckoutButtonData->getLoginEnabled());
        static::assertSame($expectedExpressCheckoutButtonData->getListingEnabled(), $actualExpressCheckoutButtonData->getListingEnabled());
        static::assertSame($expectedExpressCheckoutButtonData->getListingEnabled(), $actualExpressCheckoutButtonData->getListingEnabled());
        static::assertSame($expectedExpressCheckoutButtonData->getButtonColor(), $actualExpressCheckoutButtonData->getButtonColor());
        static::assertSame($expectedExpressCheckoutButtonData->getButtonShape(), $actualExpressCheckoutButtonData->getButtonShape());
        static::assertSame($expectedExpressCheckoutButtonData->getLanguageIso(), $actualExpressCheckoutButtonData->getLanguageIso());
        static::assertSame($expectedExpressCheckoutButtonData->getCartEnabled(), $actualExpressCheckoutButtonData->getCartEnabled());
        static::assertSame($expectedExpressCheckoutButtonData->getClientId(), $actualExpressCheckoutButtonData->getClientId());
        static::assertSame($expectedExpressCheckoutButtonData->getCurrency(), $actualExpressCheckoutButtonData->getCurrency());
        static::assertSame($expectedExpressCheckoutButtonData->getIntent(), $actualExpressCheckoutButtonData->getIntent());
    }

    private function getExpectedExpressCheckoutButtonDataForAddProductEvents(): ExpressCheckoutButtonData
    {
        return (new ExpressCheckoutButtonData())->assign([
            'productDetailEnabled' => true,
            'offCanvasEnabled' => true,
            'loginEnabled' => true,
            'listingEnabled' => true,
            'buttonColor' => 'gold',
            'buttonShape' => 'rect',
            'languageIso' => 'en_GB',
            'cartEnabled' => true,
            'clientId' => 'someClientId',
            'currency' => 'EUR',
            'intent' => 'capture',
            'addProductToCart' => true,
        ]);
    }

    private function getExpressCheckoutSubscriber(bool $withSettings = true, bool $disableEcsListing = false, bool $disableEcsDetail = false, ?string $excludedProductId = null): ExpressCheckoutSubscriber
    {
        $settings = $this->createSystemConfigServiceMock($withSettings ? [
            Settings::CLIENT_ID => 'someClientId',
            Settings::CLIENT_SECRET => 'someClientSecret',
            Settings::ECS_LISTING_ENABLED => !$disableEcsListing,
            Settings::ECS_DETAIL_ENABLED => !$disableEcsDetail,
            Settings::EXCLUDED_PRODUCT_IDS => \array_filter([$excludedProductId]),
        ] : []);

        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');

        return new ExpressCheckoutSubscriber(
            new PayPalExpressCheckoutDataService(
                $this->cartService,
                new LocaleCodeProvider(new LanguageRepoMock(), $this->createMock(LoggerInterface::class)),
                $router,
                $this->getContainer()->get(PaymentMethodUtil::class),
                $settings,
                new CredentialsUtil($settings),
                new CartPriceService()
            ),
            new SettingsValidationService($settings, new NullLogger()),
            $settings,
            $this->getContainer()->get(PaymentMethodUtil::class),
            new ExcludedProductValidator(
                $settings,
                $this->createMock(SalesChannelRepository::class)
            ),
            new NullLogger()
        );
    }

    private function createSalesChannelContext(?string $productId = null, bool $paymentMethodActive = true): SalesChannelContext
    {
        $salesChannelContext = $this->getContainer()->get(SalesChannelContextFactory::class)->create(
            Uuid::randomHex(),
            TestDefaults::SALES_CHANNEL
        );

        if ($productId) {
            /** @var EntityRepository $productRepo */
            $productRepo = $this->getContainer()->get('product.repository');
            $productRepo->create([
                [
                    'id' => $productId,
                    'name' => 'foo bar',
                    'manufacturer' => [
                        'id' => Uuid::randomHex(),
                        'name' => 'amazing brand',
                    ],
                    'productNumber' => 'P1234',
                    'tax' => ['id' => $this->getValidTaxId()],
                    'price' => [
                        [
                            'currencyId' => Defaults::CURRENCY,
                            'gross' => 10,
                            'net' => 12,
                            'linked' => false,
                        ],
                    ],
                    'stock' => 0,
                    'active' => true,
                    'visibilities' => [
                        [
                            'salesChannelId' => TestDefaults::SALES_CHANNEL,
                            'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                        ],
                    ],
                ],
            ], $salesChannelContext->getContext());

            $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $this->cartService->add($cart, $lineItem, $salesChannelContext);
        }

        /** @var EntityRepository $paymentMethodRepo */
        $paymentMethodRepo = $this->getContainer()->get('payment_method.repository');
        $paymentMethodId = $this->getContainer()->get(PaymentMethodUtil::class)->getPayPalPaymentMethodId($salesChannelContext->getContext());
        static::assertNotNull($paymentMethodId);

        $paymentMethodRepo->update([[
            'id' => $paymentMethodId,
            'active' => $paymentMethodActive,
        ]], $salesChannelContext->getContext());

        /** @var EntityRepository $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $paymentMethodIds = \array_unique(\array_merge(
            $salesChannelContext->getSalesChannel()->getPaymentMethodIds() ?? [],
            [$paymentMethodId]
        ));

        $salesChannelRepo->update([[
            'id' => TestDefaults::SALES_CHANNEL,
            'paymentMethods' => \array_map(static function (string $id) {
                return ['id' => $id];
            }, $paymentMethodIds),
        ]], $salesChannelContext->getContext());

        $criteria = new Criteria($paymentMethodIds);
        $criteria->addFilter(new EqualsFilter('active', true));

        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $paymentMethodRepo->search($criteria, $salesChannelContext->getContext())->getEntities();

        $salesChannelContext->getSalesChannel()->setPaymentMethodIds($paymentMethodIds);
        $salesChannelContext->getSalesChannel()->setPaymentMethods($paymentMethods);
        $this->getContainer()->get(PaymentMethodUtil::class)->reset();

        return $salesChannelContext;
    }

    private function createBuyBoxSwitchEvent(bool $paymentMethodActive = true): SwitchBuyBoxVariantEvent
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex(), $paymentMethodActive);

        /** @var SalesChannelRepository $productRepo */
        $productRepo = $this->getContainer()->get('sales_channel.product.repository');
        /** @var SalesChannelProductEntity|null $product */
        $product = $productRepo->search(new Criteria(), $salesChannelContext)->first();
        static::assertNotNull($product);

        return new SwitchBuyBoxVariantEvent(
            Uuid::randomHex(),
            $product,
            new PropertyGroupCollection(),
            new Request(),
            $salesChannelContext
        );
    }

    private function createQuickviewPageletLoadedEvent(bool $paymentMethodActive = true): QuickviewPageletLoadedEvent
    {
        $salesChannelContext = $this->createSalesChannelContext(Uuid::randomHex(), $paymentMethodActive);

        /** @var SalesChannelRepository $productRepo */
        $productRepo = $this->getContainer()->get('sales_channel.product.repository');
        $productId = $productRepo->searchIds(new Criteria(), $salesChannelContext)->firstId();

        $request = new Request([], [], ['productId' => $productId]);

        /** @var QuickviewPageletLoader|null $quickViewLoader */
        $quickViewLoader = $this->getContainer()->get(
            QuickviewPageletLoader::class,
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );

        if ($quickViewLoader === null) {
            static::markTestSkipped('SwagCmsExtensions plugin is not installed');
        }

        /** @var QuickviewPagelet $pagelet */
        $pagelet = $quickViewLoader->load($request, $salesChannelContext);

        // clean up for mock event
        $pagelet->removeExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        return new QuickviewPageletLoadedEvent(
            $pagelet,
            $salesChannelContext,
            new Request()
        );
    }

    private function createCheckoutConfirmPageLoadedEvent(
        bool $isExpressCheckout = true,
        bool $paymentMethodActive = true
    ): CheckoutConfirmPageLoadedEvent {
        $salesChannelContext = $this->createSalesChannelContext(null, $paymentMethodActive);

        $paymentMethodsFormSalesChannel = $salesChannelContext->getSalesChannel()->getPaymentMethods();
        static::assertNotNull($paymentMethodsFormSalesChannel);

        $confirmPage = new CheckoutConfirmPage();
        $confirmPage->setPaymentMethods($paymentMethodsFormSalesChannel);

        $request = new Request();
        if ($isExpressCheckout) {
            $request->query->set(PayPalPaymentHandler::PAYPAL_EXPRESS_CHECKOUT_ID, '1');
        }

        return new CheckoutConfirmPageLoadedEvent($confirmPage, $salesChannelContext, $request);
    }
}
