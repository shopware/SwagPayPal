<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
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
use Symfony\Component\Routing\RouterInterface;

class ExpressCheckoutSubscriberTest extends TestCase
{
    use IntegrationTestBehaviour;
    use ServicesTrait;

    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = ExpressCheckoutSubscriber::getSubscribedEvents();
        $expectedEvents = [
            OffcanvasCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutRegisterPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            CheckoutCartPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            ProductPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
            NavigationPageLoadedEvent::class => 'addExpressCheckoutDataToPage',
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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');

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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');

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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');

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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');

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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');

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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');
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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageNavigationPageLoadedEventWithEcsListindDisabled(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $event = new NavigationPageLoadedEvent(
            new NavigationPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber(true, true)->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');
        static::assertNull($actualExpressCheckoutButtonData);
    }

    public function testAddExpressCheckoutDataToPageWithUnknownEvent(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        /** @var mixed $event */
        $event = new AccountLoginPageLoadedEvent(
            new AccountLoginPage(),
            $salesChannelContext,
            new Request()
        );

        $this->getExpressCheckoutSubscriber()->addExpressCheckoutDataToPage($event);

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');
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

        /** @var ExpressCheckoutButtonData $actualExpressCheckoutButtonData */
        $actualExpressCheckoutButtonData = $event->getPage()->getExtension('payPalExpressData');
        static::assertNull($actualExpressCheckoutButtonData);
    }

    private function assertExpressCheckoutButtonData(
        ExpressCheckoutButtonData $expectedExpressCheckoutButtonData,
        ExpressCheckoutButtonData $actualExpressCheckoutButtonData
    ): void {
        static::assertInstanceOf(ExpressCheckoutButtonData::class, $actualExpressCheckoutButtonData);
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

    private function getExpressCheckoutSubscriber(bool $withSettings = true, bool $disableEcsListing = false): ExpressCheckoutSubscriber
    {
        if ($withSettings) {
            $settings = new SwagPayPalSettingStruct();
            $settings->setClientId('someClientId');
            $settings->setClientSecret('someClientSecret');
            $settings->setEcsListingEnabled(!$disableEcsListing);
        }

        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);
        /** @var RouterInterface $router */
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
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);

        $context = $salesChannelContextFactory->create(
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
                    'tax' => ['id' => Uuid::randomHex(), 'taxRate' => 19, 'name' => 'tax'],
                    'price' => [
                        [
                            'currencyId' => Defaults::CURRENCY,
                            'gross' => 10,
                            'net' => 12,
                            'linked' => false,
                        ],
                    ],
                    'stock' => 0,
                ],
            ], Context::createDefaultContext());

            $lineItem = new LineItem(Uuid::randomHex(), LineItem::PRODUCT_LINE_ITEM_TYPE, $productId);

            $cart = $cartService->getCart($context->getToken(), $context);
            $cartService->add($cart, $lineItem, $context);
        }

        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(PaymentMethodRepoMock::PAYPAL_PAYMENT_METHOD_ID);

        $salesChannelEntity = $context->getSalesChannel();
        $salesChannelEntity->setPaymentMethods(new PaymentMethodCollection([
            $paymentMethod,
        ]));
        $salesChannelEntity->setId(Defaults::SALES_CHANNEL);

        return $context;
    }
}
