<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStructFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Test\Generator;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ApplePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\GooglePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout'), CoversClass(CreateOrderRoute::class)]
class CreateOrderRouteCartServiceTest extends TestCase
{
    public function testCreatePaymentUsesSubscriptionCartServiceForSubscriptionRequests(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $cart = new Cart($salesChannelContext->getToken());

        $defaultCartService = $this->createMock(CartService::class);
        $defaultCartService
            ->expects(static::never())
            ->method('getCart');

        $subscriptionCartService = $this->createMock(CartService::class);
        $subscriptionCartService
            ->expects(static::once())
            ->method('getCart')
            ->with($salesChannelContext->getToken(), $salesChannelContext, true, true)
            ->willReturn($cart);

        $response = $this->createRoute($defaultCartService, $subscriptionCartService)->createPayPalOrder(
            $salesChannelContext,
            new Request([], [], ['_subscriptionCart' => true])
        );

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('paypal-order-id', $response->getToken());
    }

    public function testCreatePaymentThrowsExceptionWithoutSubscriptionCartService(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();

        $defaultCartService = $this->createMock(CartService::class);
        $defaultCartService
            ->expects(static::never())
            ->method('getCart');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Subscription cart service is not available');

        $this->createRoute($defaultCartService, expectsOrderBuilderCall: false)->createPayPalOrder(
            $salesChannelContext,
            new Request([], [], ['_subscriptionCart' => true])
        );
    }

    private function createRoute(
        CartService $cartService,
        ?CartService $subscriptionCartService = null,
        bool $expectsOrderBuilderCall = true,
    ): CreateOrderRoute {
        $order = new Order();
        $paypalOrder = new Order();
        $paypalOrder->setId('paypal-order-id');

        $payPalOrderBuilder = $this->getMockBuilder(PayPalOrderBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getOrderFromCart'])
            ->getMock();
        $payPalOrderBuilder
            ->expects($expectsOrderBuilderCall ? static::once() : static::never())
            ->method('getOrderFromCart')
            ->with(static::isInstanceOf(Cart::class), static::isInstanceOf(SalesChannelContext::class), static::isInstanceOf(RequestDataBag::class))
            ->willReturn($order);

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects($expectsOrderBuilderCall ? static::once() : static::never())
            ->method('create')
            ->with($order, static::anything(), static::anything())
            ->willReturn($paypalOrder);

        return new CreateOrderRoute(
            $cartService,
            $this->createMock(EntityRepository::class),
            $payPalOrderBuilder,
            $this->createMock(ACDCOrderBuilder::class),
            $this->createMock(ApplePayOrderBuilder::class),
            $this->createMock(GooglePayOrderBuilder::class),
            $this->createMock(VenmoOrderBuilder::class),
            $orderResource,
            new NullLogger(),
            new PaymentTransactionStructFactory(),
            $subscriptionCartService,
        );
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $customer = (new CustomerEntity())->assign(['id' => 'customer-id']);

        return Generator::createSalesChannelContext(
            baseContext: Context::createDefaultContext(),
            customer: $customer,
            token: 'test-token',
        );
    }
}
