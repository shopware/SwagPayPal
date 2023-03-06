<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CategoryRouteResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutButtonData;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutSubscriber;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCategoryRoute;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\PaymentMethodTrait;
use Swag\PayPal\Util\PaymentMethodUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
class ExpressCategoryRouteTest extends TestCase
{
    use IntegrationTestBehaviour;
    use PaymentMethodTrait;

    public function tearDown(): void
    {
        $paymentMethodId = $this->getContainer()->get(PaymentMethodUtil::class)->getPayPalPaymentMethodId(Context::createDefaultContext());

        if ($paymentMethodId) {
            $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId);
        }
    }

    public function testDecoration(): void
    {
        $route = $this->getContainer()->get(CategoryRoute::class);
        $foundCachedRoute = false;
        $foundExpressRoute = false;

        while ($route !== null && !$foundExpressRoute) {
            if ($route instanceof CachedCategoryRoute) {
                $foundCachedRoute = true;
            }

            if ($route instanceof ExpressCategoryRoute) {
                static::assertFalse($foundCachedRoute);
                $foundExpressRoute = true;
            }

            try {
                $route = $route->getDecorated();
            } catch (DecorationPatternException $exception) {
                $route = null;
            }
        }
        static::assertTrue($foundExpressRoute);
    }

    public function testLoadAnyRoute(): void
    {
        $response = $this->getContainer()->get(CategoryRoute::class)->load(
            $this->getValidCategoryId(),
            new Request(),
            $this->getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL)
        );

        $cmsPage = $response->getCategory()->getCmsPage();
        static::assertNotNull($cmsPage);
        static::assertFalse($cmsPage->hasExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID));
    }

    public function testLoadCmsNavigationRoute(): void
    {
        $response = $this->loadCmsNavigationRoute(true, true, true);
        $cmsPage = $response->getCategory()->getCmsPage();
        static::assertNotNull($cmsPage);
        $extension = $cmsPage->getExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID);
        static::assertInstanceOf(ExpressCheckoutButtonData::class, $extension);
        $this->assertExpressCheckoutButtonData($this->getExpectedExpressCheckoutButtonData(), $extension);
    }

    public function testLoadCmsNavigationRouteListingDisabled(): void
    {
        $response = $this->loadCmsNavigationRoute(true, false, true);

        $cmsPage = $response->getCategory()->getCmsPage();
        static::assertNotNull($cmsPage);
        static::assertFalse($cmsPage->hasExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID));
    }

    public function testLoadCmsNavigationRouteInvalidCredentials(): void
    {
        $response = $this->loadCmsNavigationRoute(false, true, true);

        $cmsPage = $response->getCategory()->getCmsPage();
        static::assertNotNull($cmsPage);
        static::assertFalse($cmsPage->hasExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID));
    }

    public function testLoadCmsNavigationRouteWithoutPayPalInSalesChannel(): void
    {
        $response = $this->loadCmsNavigationRoute(true, true, false);

        $cmsPage = $response->getCategory()->getCmsPage();
        static::assertNotNull($cmsPage);
        static::assertFalse($cmsPage->hasExtension(ExpressCheckoutSubscriber::PAYPAL_EXPRESS_CHECKOUT_BUTTON_DATA_EXTENSION_ID));
    }

    private function loadCmsNavigationRoute(bool $withCredentials, bool $listingEnabled, bool $inSalesChannel): CategoryRouteResponse
    {
        $systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $systemConfigService->set(Settings::SANDBOX, false);
        if ($withCredentials) {
            $systemConfigService->set(Settings::CLIENT_ID, 'someClientId');
            $systemConfigService->set(Settings::CLIENT_SECRET, 'someClientSecret');
        } else {
            $systemConfigService->delete(Settings::CLIENT_ID);
            $systemConfigService->delete(Settings::CLIENT_SECRET);
        }

        $systemConfigService->set(Settings::ECS_LISTING_ENABLED, $listingEnabled);

        $paymentMethodId = $this->getContainer()->get(PaymentMethodUtil::class)->getPayPalPaymentMethodId(Context::createDefaultContext());
        static::assertNotNull($paymentMethodId);

        if ($inSalesChannel) {
            $this->addPaymentMethodToDefaultsSalesChannel($paymentMethodId);
        } else {
            $this->removePaymentMethodFromDefaultsSalesChannel($paymentMethodId);
        }
        $this->getContainer()->get(PaymentMethodUtil::class)->reset();

        $salesChannelContext = $this->getContainer()->get(SalesChannelContextFactory::class)->create(Uuid::randomHex(), TestDefaults::SALES_CHANNEL);
        $request = new Request([], [], [
            '_route' => 'frontend.cms.navigation.page',
        ]);

        return $this->getContainer()->get(CategoryRoute::class)->load($this->getValidCategoryId(), $request, $salesChannelContext);
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

    private function getExpectedExpressCheckoutButtonData(): ExpressCheckoutButtonData
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
}
