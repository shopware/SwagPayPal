<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\CheckoutException;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\PayPalSDK\Struct\V2\OrderShippingCallback;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\OrdersApi\Builder\Util\ShippingOptionsProvider;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;

#[Package('checkout')]
class ExpressShippingCallbackService
{
    /**
     * @internal
     *
     * @param EntityRepository<CountryCollection> $countryRepository
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly EntityRepository $countryRepository,
        private readonly AbstractOrderBuilder $orderBuilder,
        private readonly ShippingOptionsProvider $shippingOptionsProvider,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function recalculateCart(
        OrderShippingCallback $callback,
        SalesChannelContext $salesChannelContext,
    ): Order {
        $salesChannelContext = $this->switchSalesChannelContext($callback, $salesChannelContext);

        // Recalculate cart with new context
        $this->logger->debug('Shipping callback: recalculating cart with new context');
        $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext, false, true);

        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $order->setId($callback->getId());
        $order->getPurchaseUnits()->first()?->setReferenceId((string) $callback->getPurchaseUnits()->first()?->getReferenceId());
        $order->getPurchaseUnits()->first()?->setShippingOptions($this->shippingOptionsProvider->getShippingOptions($salesChannelContext, $cart));

        $this->logger->debug('Shipping callback: cart recalculated', ['order' => $order]);

        return $order;
    }

    private function switchSalesChannelContext(OrderShippingCallback $callback, SalesChannelContext $salesChannelContext): SalesChannelContext
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $callback->getShippingAddress()->getCountryCode()));
        $criteria->setLimit(1);
        $criteria->getAssociation('states')
            ->addFilter(new EqualsFilter('name', $callback->getShippingAddress()->getAdminArea1()))
            ->setLimit(1);

        $country = $this->countryRepository->search($criteria, $salesChannelContext->getContext())->getEntities()->first();
        if (!$country) {
            throw CheckoutException::expressCountryNotFound($callback->getShippingAddress()->getCountryCode());
        }

        // The current context should not be affected by the shipping country
        // That the customer selects in the PayPal UI
        // Hence a new context for the cart recalculation is created
        $params = \array_filter([
            SalesChannelContextService::COUNTRY_ID => $country->getId(),
            SalesChannelContextService::COUNTRY_STATE_ID => $country->getStates()?->first()?->getId(),
            SalesChannelContextService::SHIPPING_METHOD_ID => $callback->getShippingOption()?->getId(),
        ]);

        $this->logger->debug('Shipping callback: switching context to new country', ['context_parameters' => $params]);
        $token = $this->contextSwitchRoute->switchContext(new RequestDataBag($params), $salesChannelContext)->getToken();

        return dump($this->salesChannelContextFactory->create($token, $salesChannelContext->getSalesChannelId(), $params));
    }
}
