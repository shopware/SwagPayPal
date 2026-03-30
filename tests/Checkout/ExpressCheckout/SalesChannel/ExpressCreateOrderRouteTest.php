<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Exception\OrderZeroValueException;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\CheckoutRouteTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class ExpressCreateOrderRouteTest extends TestCase
{
    use CheckoutRouteTrait;
    use IntegrationTestBehaviour;

    private TestHandler $logger;

    protected function setUp(): void
    {
        $this->logger = new TestHandler();
    }

    public function testCreatePaymentWithZeroValueCart(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $cart = new Cart('token');
        $cart->add(new LineItem('test', LineItem::PRODUCT_LINE_ITEM_TYPE, 'test'));

        $cartService = $this->createMock(CartService::class);
        $cartService->method('getCart')->willReturn($cart);

        $route = new ExpressCreateOrderRoute(
            $cartService,
            $this->getContainer()->get(PayPalOrderBuilder::class),
            new OrderResource(new PayPalClientFactoryMock(new NullLogger())),
            $this->getContainer()->get(CartPriceService::class),
            $this->getContainer()->get(SystemConfigService::class),
            $this->createMock(RouterInterface::class),
            new NullLogger(),
        );

        static::expectException(OrderZeroValueException::class);

        $route->createPayPalOrder(new Request(), $salesChannelContext);
    }

    public function testCreatePayment(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $response = $this->createRoute()->createPayPalOrder(new Request(), $salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());

        static::assertTrue($this->logger->hasDebug('Configured shipping callback'));
    }

    public function testCreateWithLocalEnvironmentActive(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $response = $this->createRoute([Settings::IS_LOCAL_ENVIRONMENT => true])->createPayPalOrder(new Request(), $salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());

        static::assertTrue($this->logger->hasDebug('Skipped shipping callback due to being disabled in system config'));
    }

    public function testCreateWithShippingCallbackDisabled(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $response = $this->createRoute([Settings::ECS_SHIPPING_CALLBACK_ENABLED => false])->createPayPalOrder(new Request(), $salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());

        static::assertTrue($this->logger->hasDebug('Skipped shipping callback due to being disabled in system config'));
    }

    public function testCreateShippingCallbackStoreApi(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(static::once())
            ->method('generate')
            ->with('store-api.paypal.express.shipping_callback')
            ->willReturn('generatedUrl');

        $response = $this->createRoute([], $router)->createPayPalOrder(new Request(), $salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());

        $request = $this->getClient()->getLast();
        static::assertNotNull($request);

        $order = (new Order())->assign($request->getRequestBody() ?? []);

        $experienceContext = $order->getPaymentSource()?->getPaypal()?->getExperienceContext();
        static::assertNotNull($experienceContext);
        static::assertNotNull($experienceContext->getOrderUpdateCallbackConfig());
    }

    public function testCreateShippingCallbackStorefront(): void
    {
        $salesChannelContext = $this->getSalesChannelContext();

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(static::once())
            ->method('generate')
            ->with('frontend.paypal.express.shipping_callback')
            ->willReturn('generatedUrl');

        $request = new Request();
        $request->attributes->set(PlatformRequest::ATTRIBUTE_ROUTE_SCOPE, ['storefront']);

        $response = $this->createRoute([], $router)->createPayPalOrder($request, $salesChannelContext);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());

        $request = $this->getClient()->getLast();
        static::assertNotNull($request);

        $order = (new Order())->assign($request->getRequestBody() ?? []);

        $experienceContext = $order->getPaymentSource()?->getPaypal()?->getExperienceContext();
        static::assertNotNull($experienceContext);
        static::assertNotNull($experienceContext->getOrderUpdateCallbackConfig());
    }

    /**
     * @param array<string, mixed> $systemConfigSettings
     */
    private function createRoute(
        array $systemConfigSettings = [],
        ?RouterInterface $router = null,
    ): ExpressCreateOrderRoute {
        $systemConfig = $this->createSystemConfigServiceMock([
            Settings::CLIENT_ID => 'testClientId',
            Settings::CLIENT_SECRET => 'testClientSecret',
            Settings::ECS_SHIPPING_CALLBACK_ENABLED => true,
            ...$systemConfigSettings,
        ]);

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();
        $itemListProvider = new ItemListProvider($priceFormatter, $this->createMock(EventDispatcherInterface::class), new NullLogger());

        $paypalOrderBuilder = new PayPalOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            $itemListProvider,
            $this->createMock(VaultTokenService::class),
        );

        return new ExpressCreateOrderRoute(
            $this->getContainer()->get(CartService::class),
            $paypalOrderBuilder,
            new OrderResource(new PayPalClientFactoryMock(new NullLogger())),
            $this->getContainer()->get(CartPriceService::class),
            $systemConfig,
            $this->createMock(RouterInterface::class),
            new Logger('test', [$this->logger]),
        );
    }
}
