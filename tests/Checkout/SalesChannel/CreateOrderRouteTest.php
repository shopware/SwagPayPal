<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\SalesChannel\AbstractSetPaymentOrderRoute;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Generator;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ApplePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\GooglePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\AddressProvider;
use Swag\PayPal\OrdersApi\Builder\Util\AmountProvider;
use Swag\PayPal\OrdersApi\Builder\Util\ItemListProvider;
use Swag\PayPal\OrdersApi\Builder\Util\PurchaseUnitProvider;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\SalesChannelContextTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\CustomIdProviderMock;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPalSDK\ApiContextFactoryMock;
use Swag\PayPal\Test\Mock\PayPalSDK\GatewayTestBehaviour;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
class CreateOrderRouteTest extends TestCase
{
    use GatewayTestBehaviour;
    use IntegrationTestBehaviour;
    use PaymentTransactionTrait;
    use SalesChannelContextTrait;
    use ServicesTrait;

    /**
     * @var StaticEntityRepository<OrderCollection>
     */
    private StaticEntityRepository $orderRepository;

    private CreateOrderRoute $route;

    private MockObject&AbstractSetPaymentOrderRoute $paymentOrderRoute;

    protected function setUp(): void
    {
        $this->orderRepository = new StaticEntityRepository([]);
        $systemConfig = self::createSystemConfigServiceMock([
            Settings::CLIENT_ID => 'testClientId',
            Settings::CLIENT_SECRET => 'testClientSecret',
        ]);

        $priceFormatter = new PriceFormatter();
        $amountProvider = new AmountProvider($priceFormatter);
        $addressProvider = new AddressProvider();
        $customIdProvider = new CustomIdProviderMock();
        $itemListProvider = new ItemListProvider($priceFormatter, $this->createMock(EventDispatcherInterface::class), new NullLogger());

        $acdcOrderBuilder = new ACDCOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            $itemListProvider,
            $this->createMock(VaultTokenService::class),
        );

        $venmoOrderBuilder = new VenmoOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            $itemListProvider,
            $this->createMock(VaultTokenService::class),
        );

        $applePayOrderBuilder = new ApplePayOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            $itemListProvider
        );

        $googlePayOrderBuilder = new GooglePayOrderBuilder(
            $systemConfig,
            new PurchaseUnitProvider($amountProvider, $addressProvider, $customIdProvider, $systemConfig),
            $addressProvider,
            $this->createMock(LocaleCodeProvider::class),
            $itemListProvider,
        );

        $this->route = new CreateOrderRoute(
            $this->getContainer()->get(CartService::class),
            $this->orderRepository,
            $this->createOrderBuilder($systemConfig),
            $acdcOrderBuilder,
            $applePayOrderBuilder,
            $googlePayOrderBuilder,
            $venmoOrderBuilder,
            new OrderResource(self::orderGateway(), new ApiContextFactoryMock()),
            new NullLogger(),
            new PaymentTransactionStructFactory(),
            $this->paymentOrderRoute = $this->createMock(AbstractSetPaymentOrderRoute::class),
        );
    }

    #[DataProvider('dataProviderTestCreatePayment')]
    public function testCreatePayment(bool $withCartLineItems): void
    {
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection(),
            null,
            true,
            false,
            $withCartLineItems
        );

        $response = $this->route->createPayPalOrder($salesChannelContext, new Request());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());
    }

    public function testCreatePaymentUsesRequestReturnUrls(): void
    {
        $salesChannelContext = $this->createSalesChannelContext(
            $this->getContainer(),
            new PaymentMethodCollection(),
            null,
            true,
            false,
            true
        );

        $response = $this->route->createPayPalOrder($salesChannelContext, new Request([], [
            CreateOrderRoute::PAYPAL_RETURN_URL => 'https://example.test/paypal/restore-context/token',
            CreateOrderRoute::PAYPAL_CANCEL_URL => 'https://example.test/paypal/restore-context/token',
        ]));

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());

        $request = $this->getClient()->getLast();
        static::assertNotNull($request);

        $order = (new Order())->assign($request->getRequestBody() ?? []);
        $experienceContext = $order->getPaymentSource()?->getPaypal()?->getExperienceContext();

        static::assertSame('https://example.test/paypal/restore-context/token', $experienceContext?->getReturnUrl());
        static::assertSame('https://example.test/paypal/restore-context/token', $experienceContext?->getCancelUrl());
    }

    public function testCreatePaymentWithoutCustomer(): void
    {
        $salesChannelContext = Generator::generateSalesChannelContext();
        $salesChannelContext->assign(['customer' => null]);

        $this->expectException(CustomerNotLoggedInException::class);
        $this->route->createPayPalOrder($salesChannelContext, new Request());
    }

    public function testCreatePaymentWithOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $orderId = Uuid::randomHex();
        $request = new Request([], ['orderId' => $orderId]);

        $this->paymentOrderRoute
            ->expects($this->once())
            ->method('setPayment');

        $orderEntity = $this->createOrderEntity($orderId);
        $orderTransaction = $this->createOrderTransaction();
        $orderTransaction->setOrderId($orderEntity->getId());
        $orderTransaction->setPaymentMethodId(Uuid::randomHex());
        $orderEntity->setTransactions(new OrderTransactionCollection([$orderTransaction]));
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(Uuid::randomHex());
        $orderEntity->setSalesChannel($salesChannel);

        $this->orderRepository->addSearch(new OrderCollection([$orderEntity]));

        $response = $this->route->createPayPalOrder($salesChannelContext, $request);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame(CreateOrderCapture::ID, $response->getToken());
    }

    public function testCreatePaymentWithoutOrder(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());
        $this->orderRepository->addSearch(new OrderCollection());

        $this->paymentOrderRoute
            ->expects($this->once())
            ->method('setPayment');

        $request = new Request([], ['orderId' => 'no-order-id']);

        $this->expectException(ShopwareHttpException::class);
        $this->expectExceptionMessageMatches('/Could not find order with id \"noorderid\"/');
        $this->route->createPayPalOrder($salesChannelContext, $request);
    }

    public function testCreatePaymentWithOrderWithoutTransactions(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());

        $this->paymentOrderRoute
            ->expects($this->once())
            ->method('setPayment');

        $orderEntity = $this->createOrderEntity('no-order-transactions-id');
        $this->orderRepository->addSearch(new OrderCollection([$orderEntity]));

        $request = new Request([], ['orderId' => 'no-order-transactions-id']);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The order with id noordertransactionsid is invalid or could not be found.', '/') . '\z/');
        $this->route->createPayPalOrder($salesChannelContext, $request);
    }

    public function testCreatePaymentWithOrderWithoutTransaction(): void
    {
        $salesChannelContext = $this->createSalesChannelContext($this->getContainer(), new PaymentMethodCollection());

        $this->paymentOrderRoute
            ->expects($this->once())
            ->method('setPayment');

        $orderEntity = $this->createOrderEntity('no-order-transaction-id');
        $orderEntity->setTransactions(new OrderTransactionCollection());
        $this->orderRepository->addSearch(new OrderCollection([$orderEntity]));

        $request = new Request([], ['orderId' => 'no-order-transaction-id']);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessageMatches('/\A' . \preg_quote('The order with id noordertransactionid is invalid or could not be found.', '/') . '\z/');
        $this->route->createPayPalOrder($salesChannelContext, $request);
    }

    public static function dataProviderTestCreatePayment(): array
    {
        return [[true], [false]];
    }
}
