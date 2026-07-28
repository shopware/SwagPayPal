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
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\TaxProvider\TaxProviderProcessor;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressPrepareCheckoutRoute::class)]
class ExpressPrepareCheckoutRouteTaxedCartTest extends TestCase
{
    public function testPrepareLoadsTaxedCartThroughCartLoadRoute(): void
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId('sales-channel-id');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getSalesChannel')
            ->willReturn($salesChannel);

        $newToken = 'new-context-token';
        $cart = new Cart($newToken);
        $cart->add(new LineItem('test', LineItem::PRODUCT_LINE_ITEM_TYPE, 'test'));

        $taxProviderProcessor = $this->createMock(TaxProviderProcessor::class);
        $taxProviderProcessor
            ->expects(static::once())
            ->method('process')
            ->with($cart, $salesChannelContext);

        $newSalesChannelContext = $this->createMock(SalesChannelContext::class);
        $newSalesChannelContext
            ->method('getToken')
            ->willReturn($newToken);

        $expressCustomerService = $this->createMock(ExpressCustomerService::class);
        $expressCustomerService
            ->expects(static::once())
            ->method('loginCustomer')
            ->willReturn($newToken);

        $salesChannelContextFactory = $this->createMock(AbstractSalesChannelContextFactory::class);
        $salesChannelContextFactory
            ->expects(static::once())
            ->method('create')
            ->with($newToken, $salesChannel->getId())
            ->willReturn($newSalesChannelContext);

        $orderResource = $this->createMock(OrderResource::class);
        $orderResource
            ->expects(static::once())
            ->method('get')
            ->with('paypal-order-id', $salesChannel->getId())
            ->willReturn(new Order());

        $cartPriceService = $this->createMock(CartPriceService::class);
        $cartPriceService
            ->expects(static::once())
            ->method('validateProcessable')
            ->with($cart, $newSalesChannelContext);

        $route = new ExpressPrepareCheckoutRoute(
            $expressCustomerService,
            $salesChannelContextFactory,
            $orderResource,
            $this->createTaxedCartService($cart, $salesChannelContext, $newSalesChannelContext, $taxProviderProcessor),
            $cartPriceService,
            new NullLogger(),
        );

        $response = $route->prepareCheckout($salesChannelContext, new Request([], [
            PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN => 'paypal-order-id',
        ]));

        static::assertSame($newToken, $response->getToken());
        static::assertInstanceOf(
            ExpressCheckoutData::class,
            $cart->getExtension(ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID)
        );
    }

    private function createTaxedCartService(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        SalesChannelContext $newSalesChannelContext,
        TaxProviderProcessor $taxProviderProcessor,
    ): CartService {
        $persister = $this->createMock(AbstractCartPersister::class);
        $persister
            ->expects(static::once())
            ->method('load')
            ->with($cart->getToken(), $salesChannelContext)
            ->willReturn($cart);
        $persister
            ->expects(static::once())
            ->method('save')
            ->with($cart, $newSalesChannelContext);

        $calculator = $this->createMock(CartCalculator::class);
        $calculator
            ->expects(static::exactly(2))
            ->method('calculate')
            ->willReturn($cart);

        return new CartService(
            $persister,
            $this->createMock(EventDispatcherInterface::class),
            $calculator,
            new CartLoadRoute(
                $persister,
                $this->createMock(CartFactory::class),
                $calculator,
                $taxProviderProcessor,
            ),
            $this->createMock(AbstractCartDeleteRoute::class),
            $this->createMock(AbstractCartItemAddRoute::class),
            $this->createMock(AbstractCartItemUpdateRoute::class),
            $this->createMock(AbstractCartItemRemoveRoute::class),
            $this->createMock(AbstractCartOrderRoute::class),
            $this->createMock(CartFactory::class),
        );
    }
}
