<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Checkout\ExpressCheckout;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
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
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Symfony\Component\HttpFoundation\Request;

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
        $actualExpressCheckoutButtonData = $event->getPage()->getExtensions()['payPalExpressData'];

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
        $actualExpressCheckoutButtonData = $event->getPage()->getExtensions()['payPalExpressData'];

        $this->assertExpressCheckoutButtonData(
            $this->getExpectedExpressCheckoutButtonDataForAddProductEvents(),
            $actualExpressCheckoutButtonData
        );
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
        static::assertSame($expectedExpressCheckoutButtonData->getUseSandbox(), $actualExpressCheckoutButtonData->getUseSandbox());
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
            'useSandbox' => false,
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

    private function getExpressCheckoutSubscriber(): ExpressCheckoutSubscriber
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('someClientId');
        $settings->setClientSecret('someClientSecret');

        /** @var CartService $cartService */
        $cartService = $this->getContainer()->get(CartService::class);

        return new ExpressCheckoutSubscriber(
            new PayPalExpressCheckoutDataService(
                $cartService,
                $this->createLocaleCodeProvider()
            ),
            new SettingsServiceMock($settings)
        );
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        /** @var SalesChannelContextFactory $salesChannelContextFactory */
        $salesChannelContextFactory = $this->getContainer()->get(SalesChannelContextFactory::class);

        return $salesChannelContextFactory->create(
            Uuid::randomHex(),
            Defaults::SALES_CHANNEL
        );
    }
}
