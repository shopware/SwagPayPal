<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Tax\TaxDefinition;
use Shopware\Storefront\Page\Account\Login\AccountLoginPage;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPage;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPage;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPage;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPage;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPagelet;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoadedEvent;
use Swag\CmsExtensions\Storefront\Pagelet\Quickview\QuickviewPageletLoader;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\Service\PayPalExpressCheckoutDataService;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\Repositories\PaymentMethodRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

class ExpressCheckoutSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = ExpressCheckoutSubscriber::getSubscribedEvents();
        $expectedEvents = [
            CheckoutCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            NavigationPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            OffcanvasCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::class => 'addExpressCheckoutDataToPage',

            CmsPageLoadedEvent::class => 'addExpressCheckoutDataToCmsPage',

            QuickviewPageletLoadedEvent::class => 'addExpressCheckoutDataToPagelet',
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
        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);
        $cartService->createNew($salesChannelContext->getToken());
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

    public function testAddExpressCheckoutDataToCmsPageCmsPageLoadedEvent(): void
    {
        $event = $this->createCmsPageLoadedEvent();

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToCmsPage($event);

        $cmsPage = $event->getResult()->first();
        static::assertNotNull($cmsPage);
        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $cmsPage->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
    }

    public function testAddExpressCheckoutDataToCmsPageCmsWithoutPayPalInSalesChannel(): void
    {
        $event = $this->createCmsPageLoadedEvent();
        $event->getSalesChannelContext()->getSalesChannel()->setId(Uuid::randomHex());

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToCmsPage($event);

        $cmsPage = $event->getResult()->first();
        static::assertNotNull($cmsPage);
        /** @var ExpressCheckoutButtonData|null $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $cmsPage->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToCmsPageCmsWithoutCmsPage(): void
    {
        $event = $this->createCmsPageLoadedEvent(false);

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToCmsPage($event);

        $cmsPage = $event->getResult()->first();
        static::assertNull($cmsPage);
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
            'intent' => 'sale',
            'addProductToCart' => true,
        ]);
    }

    private function getExpressCheckoutSubscriber(bool $withSettings = true, bool $disableEcsListing = false, bool $disableEcsDetail = false): ExpressCheckoutSubscriber
    {
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('someClientId');
            $settings->setClientSecret('someClientSecret');
            $settings->setEcsListingEnabled(!$disableEcsListing);
            $settings->setEcsDetailEnabled(!$disableEcsDetail);
        }

        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);
        $router = $this->getContainer()->get('router');

        return new ExpressCheckoutSubscriber(
            new PayPalExpressCheckoutDataService(
                $cartService,
                $this->createLocaleCodeProvider(),
                $router
            ),
            new SettingsServiceMock($settings ?? null),
            new PaymentMethodUtil(
                new PaymentMethodRepoMock(),
                new SalesChannelRepoMock()
            )
        );
    }

    private function createSalesChannelContext(bool $withItemList = false): SalesChannelContext
    {
        $taxId = $this->createTaxId(Context::createDefaultContext());
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);

        $salesChannelContext = $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL
        );

        if ($withItemList) {
            /** @var CartService $cartService */
            $cartService = $this->getContainer()->get(CartService::class);
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
                    'tax' => ['id' => $taxId],
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

            $cart = $cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $cartService->add($cart, $lineItem, $salesChannelContext);
        }

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID);

        $salesChannelEntity = $salesChannelContext->getSalesChannel();
        $salesChannelEntity->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod,
        ]));
        $salesChannelEntity->setId(Defaults::SALES_CHANNEL);

        return $salesChannelContext;
    }

    private function createTaxId(Context $context): string
    {
        /** @var EntityRepositoryInterface $taxRepo */
        $taxRepo = $this->getContainer()->get(TaxDefinition::ENTITY_NAME . '.repository');
        $taxId = Uuid::randomHex();
        $taxData = [
            [
                'id' => $taxId,
                'taxRate' => 19.0,
                'name' => 'testTaxRate',
            ],
        ];

        $taxRepo->create($taxData, $context);

        return $taxId;
    }

    private function createCmsPageLoadedEvent(bool $hasCmsPage = true): CmsPageLoadedEvent
    {
        $cmsPages = [];
        if ($hasCmsPage) {
            $cmsPage = new CmsPageEntity();
            $cmsPage->setId('cms-page-test-id');
            $cmsPages[] = $cmsPage;
        }

        $result = new CmsPageCollection($cmsPages);

        return new CmsPageLoadedEvent(
            new Request(),
            $result,
            $this->createSalesChannelContext(true)
        );
    }

    private function createQuickviewPageletLoadedEvent(): QuickviewPageletLoadedEvent
    {
        $salesChannelContext = $this->createSalesChannelContext(true);

        /** @var EntityRepositoryInterface $productRepo */
        $productRepo = $this->getContainer()->get('product.repository');
        $product = $productRepo->search(new Criteria(), $salesChannelContext->getContext())->first();

        $request = new Request([], [], ['productId' => $product->getId()]);

        /** @var QuickviewPageletLoader $quickViewLoader */
        $quickViewLoader = $this->getContainer()->get(QuickviewPageletLoader::class);
        /** @var QuickviewPagelet $pagelet */
        $pagelet = $quickViewLoader->load($request, $salesChannelContext);

        return new QuickviewPageletLoadedEvent(
            $pagelet,
            $salesChannelContext,
            new Request()
        );
    }
}
