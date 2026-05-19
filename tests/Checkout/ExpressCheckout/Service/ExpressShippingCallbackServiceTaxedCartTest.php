<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\ExpressCheckout\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Cart\AbstractCartPersister;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartCalculator;
use Shopware\Core\Checkout\Cart\CartFactory;
use Shopware\Core\Checkout\Cart\Delivery\Struct\ShippingLocation;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemAddRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemRemoveRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartItemUpdateRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartLoadRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Cart\TaxProvider\TaxProviderProcessor;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\Aggregate\CountryState\CountryStateEntity;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Checkout\Shipping\BlockedShippingMethodSwitcher;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\ShippingOptionsProvider;
use Swag\PayPal\RestApi\V2\Api\Common\Address;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ShippingOption;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\ShippingOptionCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\RestApi\V2\Api\OrderShippingCallback;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(ExpressShippingCallbackService::class)]
class ExpressShippingCallbackServiceTaxedCartTest extends TestCase
{
    public function testCalculationWithoutChangesLoadsTaxedCartThroughCartLoadRoute(): void
    {
        $salesChannelContext = $this->createSalesChannelContext();
        $cart = new Cart('shipping-token');

        $taxProviderProcessor = $this->createMock(TaxProviderProcessor::class);
        $taxProviderProcessor
            ->expects(static::once())
            ->method('process')
            ->with($cart, $salesChannelContext);

        $order = new Order();
        $order->setPurchaseUnits(new PurchaseUnitCollection([new PurchaseUnit()]));

        $orderBuilder = $this->createMock(AbstractOrderBuilder::class);
        $orderBuilder
            ->expects(static::once())
            ->method('getOrderFromCart')
            ->with($cart, $salesChannelContext, static::isInstanceOf(RequestDataBag::class))
            ->willReturn($order);

        $shippingOption = new ShippingOption();
        $shippingOption->setId($salesChannelContext->getShippingMethod()->getId());
        $shippingOption->setLabel('Standard');

        $shippingOptionsProvider = $this->createMock(ShippingOptionsProvider::class);
        $shippingOptionsProvider
            ->expects(static::once())
            ->method('getShippingOptions')
            ->with($cart, $salesChannelContext)
            ->willReturn(new ShippingOptionCollection([$shippingOption]));

        $blockedShippingMethodSwitcher = $this->createMock(BlockedShippingMethodSwitcher::class);
        $blockedShippingMethodSwitcher
            ->expects(static::once())
            ->method('switch')
            ->willReturn($salesChannelContext->getShippingMethod());

        $service = new ExpressShippingCallbackService(
            $this->createTaxedCartService($cart, $salesChannelContext, $taxProviderProcessor),
            $this->createMock(SalesChannelRepository::class),
            $orderBuilder,
            $shippingOptionsProvider,
            $this->createMock(AbstractContextSwitchRoute::class),
            $this->createMock(AbstractSalesChannelContextFactory::class),
            $blockedShippingMethodSwitcher,
            new NullLogger(),
        );

        $result = $service->recalculateCart($this->createCallback(), $salesChannelContext);

        $serialized = \json_decode((string) \json_encode($result, \JSON_THROW_ON_ERROR), true, flags: \JSON_THROW_ON_ERROR);

        static::assertSame(['id', 'purchase_units'], \array_keys($serialized));
        static::assertSame('paypal-order-id', $serialized['id']);
        static::assertSame('default', $serialized['purchase_units'][0]['reference_id']);
        static::assertCount(1, $result->getPurchaseUnits()->first()?->getShippingOptions() ?? []);
    }

    private function createSalesChannelContext(): SalesChannelContext
    {
        $country = new CountryEntity();
        $country->setId('country-id');
        $country->setIso('DE');

        $state = new CountryStateEntity();
        $state->setId('state-id');
        $state->setCountryId($country->getId());
        $state->setCountry($country);
        $state->setShortCode('BE');

        $address = new CustomerAddressEntity();
        $address->setId('address-id');
        $address->setCountryId($country->getId());
        $address->setCountry($country);
        $address->setCountryStateId($state->getId());
        $address->setCountryState($state);

        $shippingMethod = new ShippingMethodEntity();
        $shippingMethod->setId('shipping-method-id');
        $shippingMethod->setName('Standard');

        $salesChannelContext = $this->createMock(SalesChannelContext::class);
        $salesChannelContext
            ->method('getToken')
            ->willReturn('shipping-token');
        $salesChannelContext
            ->method('getShippingLocation')
            ->willReturn(ShippingLocation::createFromAddress($address));
        $salesChannelContext
            ->method('getShippingMethod')
            ->willReturn($shippingMethod);

        return $salesChannelContext;
    }

    private function createCallback(): OrderShippingCallback
    {
        $address = new Address();
        $address->setCountryCode('DE');
        $address->setAdminArea1('BE');

        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setReferenceId('default');

        $callback = new OrderShippingCallback();
        $callback->setId('paypal-order-id');
        $callback->setShippingAddress($address);
        $callback->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));

        return $callback;
    }

    private function createTaxedCartService(
        Cart $cart,
        SalesChannelContext $salesChannelContext,
        TaxProviderProcessor $taxProviderProcessor,
    ): CartService {
        $persister = $this->createMock(AbstractCartPersister::class);
        $persister
            ->expects(static::once())
            ->method('load')
            ->with($cart->getToken(), $salesChannelContext)
            ->willReturn($cart);

        $calculator = $this->createMock(CartCalculator::class);
        $calculator
            ->expects(static::once())
            ->method('calculate')
            ->with($cart, $salesChannelContext)
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
