<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Property\PropertyGroupCollection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\SwitchBuyBoxVariantEvent;
use Shopware\Storefront\Page\Account\Login\AccountLoginPage;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
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
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

class ExpressCheckoutSubscriberTest extends TestCase
{
    use ServicesTrait;

    private CartService $cartService;

    public function setUp(): void
    {
        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);
        $this->cartService = $cartService;
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

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToPageOffCanvasCartPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(true);
        $event = new OffcanvasCartPageLoadedEvent(
            new OffcanvasCartPage(),
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

    public function testAddExpressCheckoutDataToPageCheckoutRegisterPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(true);
        $event = new CheckoutRegisterPageLoadedEvent(
            new CheckoutRegisterPage(),
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

    public function testAddExpressCheckoutDataToPageCheckoutCartPageLoadedEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(true);
        $event = new CheckoutCartPageLoadedEvent(
            new CheckoutCartPage(),
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

    public function testAddExpressCheckoutDataToPageCartPageWithZeroValue(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(true);
        $event = new CheckoutCartPageLoadedEvent(new CheckoutCartPage(), $salesChannelContext, new Request());

        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $cart->setPrice($this->getEmptyCartPrice());

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);

        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageWithoutPayPalInSalesChannel(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $salesChannelContext->getSalesChannel()->setId(Uuid::randomHex());
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
        $salesChannelContext = $this->createSalesChannelContext(true, false);
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

    public function testAddExpressCheckoutDataToPageWithUnknownEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new AccountLoginPageLoadedEvent(
            new AccountLoginPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

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
        if (!\class_exists(SwitchBuyBoxVariantEvent::class)) {
            static::markTestSkipped('Buy Box event only exists starting with Shopware 6.4.2.0');
        }

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
        if (!\class_exists(SwitchBuyBoxVariantEvent::class)) {
            static::markTestSkipped('Buy Box event only exists starting with Shopware 6.4.2.0');
        }

        $event = $this->createBuyBoxSwitchEvent(false);

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToBuyBoxSwitch($event);

        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getProduct()->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToBuyBoxSwitchWithoutPayPalInSalesChannel(): void
    {
        if (!\class_exists(SwitchBuyBoxVariantEvent::class)) {
            static::markTestSkipped('Buy Box event only exists starting with Shopware 6.4.2.0');
        }

        $event = $this->createBuyBoxSwitchEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setId(Uuid::randomHex());

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

    private function getExpressCheckoutSubscriber(bool $withSettings = true, bool $disableEcsListing = false, bool $disableEcsDetail = false): ExpressCheckoutSubscriber
    {
        $settings = $this->createSystemConfigServiceMock($withSettings ? [
            Settings::CLIENT_ID => 'someClientId',
            Settings::CLIENT_SECRET => 'someClientSecret',
            Settings::ECS_LISTING_ENABLED => !$disableEcsListing,
            Settings::ECS_DETAIL_ENABLED => !$disableEcsDetail,
        ] : []);

        /** @var RouterInterface $router */
        $router = $this->getContainer()->get('router');
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);

        return new ExpressCheckoutSubscriber(
            new PayPalExpressCheckoutDataService(
                $this->cartService,
                $this->createLocaleCodeProvider(),
                $router,
                $paymentMethodUtil,
                $settings,
                new CartPriceService()
            ),
            new SettingsValidationService($settings, new NullLogger()),
            $settings,
            $paymentMethodUtil,
            new NullLogger()
        );
    }

    private function createSalesChannelContext(bool $withItemList = false, bool $paymentMethodActive = true): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);

        $salesChannelContext = $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL
        );

        if ($withItemList) {
            /** @var EntityRepositoryInterface $productRepo */
            $productRepo = $this->getContainer()->get('product.repository');

            $productId = Uuid::randomHex();
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
                            'salesChannelId' => Defaults::SALES_CHANNEL,
                            'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                        ],
                    ],
                ],
            ], $salesChannelContext->getContext());

            $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $this->cartService->add($cart, $lineItem, $salesChannelContext);
        }

        /** @var EntityRepositoryInterface $paymentMethodRepo */
        $paymentMethodRepo = $this->getContainer()->get('payment_method.repository');
        /** @var PaymentMethodUtil $paymentMethodUtil */
        $paymentMethodUtil = $this->getContainer()->get(PaymentMethodUtil::class);
        $paymentMethodId = $paymentMethodUtil->getPayPalPaymentMethodId($salesChannelContext->getContext());
        static::assertNotNull($paymentMethodId);

        $paymentMethodRepo->update([[
            'id' => $paymentMethodId,
            'active' => $paymentMethodActive,
        ]], $salesChannelContext->getContext());

        /** @var EntityRepositoryInterface $salesChannelRepo */
        $salesChannelRepo = $this->getContainer()->get('sales_channel.repository');

        $paymentMethodIds = \array_unique(\array_merge(
            $salesChannelContext->getSalesChannel()->getPaymentMethodIds() ?? [],
            [$paymentMethodId]
        ));

        $salesChannelRepo->update([[
            'id' => Defaults::SALES_CHANNEL,
            'paymentMethods' => \array_map(static function (string $id) {
                return ['id' => $id];
            }, $paymentMethodIds),
        ]], $salesChannelContext->getContext());

        $criteria = new Criteria($paymentMethodIds);
        $criteria->addFilter(new EqualsFilter('active', true));
        $paymentMethods = $paymentMethodRepo->search($criteria, $salesChannelContext->getContext())->getEntities();
        static::assertInstanceOf(PaymentMethodCollection::class, $paymentMethods);

        $salesChannelContext->getSalesChannel()->setPaymentMethodIds($paymentMethodIds);
        $salesChannelContext->getSalesChannel()->setPaymentMethods($paymentMethods);

        return $salesChannelContext;
    }

    private function createBuyBoxSwitchEvent(bool $paymentMethodActive = true): SwitchBuyBoxVariantEvent
    {
        $salesChannelContext = $this->createSalesChannelContext(true, $paymentMethodActive);

        /** @var SalesChannelRepositoryInterface $productRepo */
        $productRepo = $this->getContainer()->get('sales_channel.product.repository');
        $product = $productRepo->search(new Criteria(), $salesChannelContext)->first();

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
        $salesChannelContext = $this->createSalesChannelContext(true, $paymentMethodActive);

        /** @var EntityRepositoryInterface $productRepo */
        $productRepo = $this->getContainer()->get('product.repository');
        $product = $productRepo->search(new Criteria(), $salesChannelContext->getContext())->first();

        $request = new Request([], [], ['productId' => $product->getId()]);

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
        $salesChannelContext = $this->createSalesChannelContext(false, $paymentMethodActive);

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
