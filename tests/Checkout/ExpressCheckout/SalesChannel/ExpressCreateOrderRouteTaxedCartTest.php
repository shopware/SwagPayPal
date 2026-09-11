<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\SalesChannel;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartFactory;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemRemoveRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemUpdateRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartResponse;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Exception\OrderZeroValueException;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressCreateOrderRoute;
use Swag\PayPal\OrdersApi\Builder\PayPalOrderBuilder;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressCreateOrderRoute::class)]
class ExpressCreateOrderRouteTaxedCartTest extends TestCase
{
    public function testCreatePaymentLoadsTaxedCartThroughCartLoadRoute(): void
    {
        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getToken')
            ->willReturn('express-token');

        $cart = new Cart('express-token');
        $cart->add(new LineItem('test', LineItem::PRODUCT_LINE_ITEM_TYPE, 'test'));

        $cartPriceService = $this->createMock(CartPriceService::class);
        $cartPriceService
            ->expects($this->once())
            ->method('validateProcessable')
            ->with($cart, $salesChannelContext)
            ->willThrowException(new OrderZeroValueException());

        $route = new ExpressCreateOrderRoute(
            $this->createTaxedCartService($cart, $salesChannelContext),
            $this->createMock(PayPalOrderBuilder::class),
            $this->createMock(OrderResource::class),
            $cartPriceService,
            $this->createMock(SystemConfigService::class),
            $this->createMock(RouterInterface::class),
            new NullLogger(),
        );

        static::expectException(OrderZeroValueException::class);

        $route->createPayPalOrder(new Request(), $salesChannelContext);
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
