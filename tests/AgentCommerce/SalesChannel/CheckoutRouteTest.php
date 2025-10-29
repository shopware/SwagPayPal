<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\AgentCommerce\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartOrderRouteResponse;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Test\Generator;
use Swag\PayPal\AgentCommerce\Exception\AgentException;
use Swag\PayPal\AgentCommerce\SalesChannel\CheckoutRoute;
use Swag\PayPal\AgentCommerce\Struct\V1\PayPalCart;
use Swag\PayPal\AgentCommerce\Util\PayPalCartTransformer;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[CoversClass(CheckoutRoute::class)]
#[Package('checkout')]
class CheckoutRouteTest extends TestCase
{
    public function testCheckoutWithInvalidCartToken(): void
    {
        $route = new CheckoutRoute(
            $this->createMock(AbstractCartOrderRoute::class),
            $this->createMock(CartService::class),
            $this->createMock(OrderResource::class),
            $this->createMock(PayPalPaymentHandler::class),
            $this->createMock(PayPalCartTransformer::class)
        );

        $this->expectExceptionObject(AgentException::invalidCartId());

        $route->checkout('invalid-token', new Request(), Generator::createSalesChannelContext());
    }

    public function testCheckoutWithEmptyCart(): void
    {
        $token = 'CART-TOKEN';

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->expects(static::once())
            ->method('getCart')
            ->with('TOKEN')
            ->willReturn(new Cart('TOKEN'));

        $route = new CheckoutRoute(
            $this->createMock(AbstractCartOrderRoute::class),
            $cartService,
            $this->createMock(OrderResource::class),
            $this->createMock(PayPalPaymentHandler::class),
            $this->createMock(PayPalCartTransformer::class)
        );

        $this->expectExceptionObject(AgentException::cartNotFound($token));

        $route->checkout($token, new Request(), Generator::createSalesChannelContext());
    }

    public function testCheckoutWithoutTransaction(): void
    {
        $token = 'CART-TOKEN';

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->expects(static::once())
            ->method('getCart')
            ->with('TOKEN')
            ->willReturn(Generator::createCart());

        $order = new OrderEntity();
        $order->setTransactions(new OrderTransactionCollection([]));

        $orderResponse = new CartOrderRouteResponse($order);

        $orderRoute = $this->createMock(AbstractCartOrderRoute::class);
        $orderRoute
            ->expects(static::once())
            ->method('order')
            ->willReturn($orderResponse);

        $route = new CheckoutRoute(
            $orderRoute,
            $cartService,
            $this->createMock(OrderResource::class),
            $this->createMock(PayPalPaymentHandler::class),
            $this->createMock(PayPalCartTransformer::class)
        );

        $this->expectExceptionObject(AgentException::orderSystemError());

        $route->checkout($token, new Request(), Generator::createSalesChannelContext());
    }

    public function testCheckout(): void
    {
        $token = 'CART-TOKEN';

        $context = Generator::createSalesChannelContext();
        $cart = Generator::createCart();

        $request = new Request(content: \json_encode(['payment_method' => ['token' => 'PAYPAL-ORDER-ID']], \JSON_THROW_ON_ERROR));

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->expects(static::once())
            ->method('getCart')
            ->with('TOKEN')
            ->willReturn($cart);

        $transaction = new OrderTransactionEntity();
        $transaction->setId('primary-order-transaction-id');

        $order = new OrderEntity();
        $order->setTransactions(new OrderTransactionCollection([$transaction]));

        $orderResponse = new CartOrderRouteResponse($order);

        $orderRoute = $this->createMock(AbstractCartOrderRoute::class);
        $orderRoute
            ->expects(static::once())
            ->method('order')
            ->willReturn($orderResponse);

        $payPalOrder = new Order();
        $payPalOrder->setId('PAYPAL-ORDER-ID');

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('PAYPAL-ORDER-ID', $context->getSalesChannelId())
            ->willReturn($payPalOrder);

        $payPalCart = new PayPalCart();
        $payPalCart->setId('PAYPAL-ORDER-ID');

        $transformer = $this->createMock(PayPalCartTransformer::class);
        $transformer
            ->expects(static::once())
            ->method('convertToPayPalCart')
            ->with($cart, $context)
            ->willReturn($payPalCart);

        $requestDataBag = new RequestDataBag($request->request->all());
        $requestDataBag->set(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME, $payPalCart->getId());

        $paymentHandler = $this->createMock(PayPalPaymentHandler::class);
        $paymentHandler
            ->expects(static::once())
            ->method('pay')
            ->with(static::equalTo(new AsyncPaymentTransactionStruct($transaction, $order, '')), $requestDataBag, $context)
            ->willThrowException(PaymentException::asyncProcessInterrupted($transaction->getId(), 'error message'));

        $route = new CheckoutRoute(
            $orderRoute,
            $cartService,
            $orderResource,
            $paymentHandler,
            $transformer
        );

        $response = $route->checkout($token, $request, $context);
        $cart = $response->getCart();

        static::assertSame(PayPalCart::STATUS__COMPLETE, $cart->getStatus());

        static::assertNotNull($cart->getPaymentMethod());
        static::assertSame('PAYPAL-ORDER-ID', $cart->getPaymentMethod()->getToken());
    }

    public function testCheckoutWithRedirect(): void
    {
        $token = 'CART-TOKEN';

        $context = Generator::createSalesChannelContext();
        $cart = Generator::createCart();

        $request = new Request(content: \json_encode(['payment_method' => ['token' => 'PAYPAL-ORDER-ID']], \JSON_THROW_ON_ERROR));

        $cartService = $this->createMock(CartService::class);
        $cartService
            ->expects(static::once())
            ->method('getCart')
            ->with('TOKEN')
            ->willReturn($cart);

        $transaction = new OrderTransactionEntity();
        $transaction->setId('primary-order-transaction-id');

        $order = new OrderEntity();
        $order->setTransactions(new OrderTransactionCollection([$transaction]));

        $orderResponse = new CartOrderRouteResponse($order);

        $orderRoute = $this->createMock(AbstractCartOrderRoute::class);
        $orderRoute
            ->expects(static::once())
            ->method('order')
            ->willReturn($orderResponse);

        $payPalOrder = new Order();
        $payPalOrder->setId('PAYPAL-ORDER-ID');

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('PAYPAL-ORDER-ID', $context->getSalesChannelId())
            ->willReturn($payPalOrder);

        $payPalCart = new PayPalCart();
        $payPalCart->setId('PAYPAL-ORDER-ID');

        $transformer = $this->createMock(PayPalCartTransformer::class);
        $transformer
            ->expects(static::once())
            ->method('convertToPayPalCart')
            ->with($cart, $context)
            ->willReturn($payPalCart);

        $requestDataBag = new RequestDataBag($request->request->all());
        $requestDataBag->set(AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME, $payPalCart->getId());

        $paymentHandler = $this->createMock(PayPalPaymentHandler::class);
        $paymentHandler
            ->expects(static::once())
            ->method('pay')
            ->with(static::equalTo(new AsyncPaymentTransactionStruct($transaction, $order, '')), $requestDataBag, $context)
            ->willReturn(new RedirectResponse('https://example.com/redirect-url'));

        $route = new CheckoutRoute(
            $orderRoute,
            $cartService,
            $orderResource,
            $paymentHandler,
            $transformer
        );

        $response = $route->checkout($token, $request, $context);
        $cart = $response->getCart();

        static::assertSame(PayPalCart::STATUS__INCOMPLETE, $cart->getStatus());
        static::assertSame(PayPalCart::VALIDATION_STATUS__INVALID, $cart->getValidationStatus());

        static::assertNotNull($cart->getPaymentMethod());
        static::assertSame('PAYPAL-ORDER-ID', $cart->getPaymentMethod()->getToken());
        static::assertSame('https://example.com/redirect-url', $cart->getPaymentMethod()->getApprovalUrl());
    }
}
