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
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartFactory;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemRemoveRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemUpdateRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartResponse;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\SalesChannel\AbstractSetPaymentOrderRoute;
use Shopware\Core\Checkout\Payment\Cart\AbstractPaymentTransactionStructFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\SalesChannel\CreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\ACDCOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\ApplePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\GooglePayOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\VenmoOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(CreateOrderRoute::class)]
class CreateOrderRouteTaxedCartTest extends TestCase
{
    public function testCreatePaymentLoadsTaxedCartThroughCartLoadRoute(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('customer-id');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getCustomer')
            ->willReturn($customer);
        $salesChannelContext
            ->method('getToken')
            ->willReturn('create-order-token');

        $cart = new Cart('create-order-token');

        $exception = new \RuntimeException('stop after taxed cart load');

        $payPalOrderBuilder = $this->createMock(PayPalOrderBuilder::class);
        $payPalOrderBuilder
            ->expects($this->once())
            ->method('getOrderFromCart')
            ->with(
                $cart,
                $salesChannelContext,
                static::callback(static function (RequestDataBag $requestDataBag): bool {
                    static::assertSame('Mozilla/5.0 Route App Switch Test', $requestDataBag->getString(CreateOrderRoute::PAYPAL_BUYER_USER_AGENT));

                    return true;
                }),
            )
            ->willThrowException($exception);

        $route = new CreateOrderRoute(
            $this->createTaxedCartService($cart, $salesChannelContext),
            $this->createMock(EntityRepository::class),
            $payPalOrderBuilder,
            $this->createMock(ACDCOrderBuilder::class),
            $this->createMock(ApplePayOrderBuilder::class),
            $this->createMock(GooglePayOrderBuilder::class),
            $this->createMock(VenmoOrderBuilder::class),
            $this->createMock(OrderResource::class),
            new NullLogger(),
            $this->createMock(AbstractPaymentTransactionStructFactory::class),
            $this->createMock(AbstractSetPaymentOrderRoute::class),
        );

        static::expectExceptionObject($exception);

        $route->createPayPalOrder($salesChannelContext, new Request([], [], [], [], [], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 Route App Switch Test',
        ]));
    }

    private function createTaxedCartService(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
    ): CartService {
        $cartLoadRoute = $this->createMock(AbstractCartLoadRoute::class);
        $cartLoadRoute
            ->expects($this->once())
            ->method('load')
            ->with(
                static::callback(static function (Request $request) use ($cart): bool {
                    static::assertSame($cart->getToken(), $request->query->get('token'));
                    static::assertTrue($request->query->getBoolean('taxed'));

                    return true;
                }),
                $salesChannelContext,
            )
            ->willReturn(new CartResponse($cart));

        return new CartService(
            $this->createMock(AbstractCartPersister::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(CartCalculator::class),
            $cartLoadRoute,
            $this->createMock(AbstractCartDeleteRoute::class),
            $this->createMock(AbstractCartItemAddRoute::class),
            $this->createMock(AbstractCartItemUpdateRoute::class),
            $this->createMock(AbstractCartItemRemoveRoute::class),
            $this->createMock(AbstractCartOrderRoute::class),
            $this->createMock(CartFactory::class),
        );
    }
}
