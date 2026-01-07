<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryCollection;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\OrderShippingCallback;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressShippingCallbackException;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\OrdersApi\Builder\Util\ShippingOptionsProvider;

#[Package('checkout')]
class ExpressShippingCallbackService
{
    /**
     * @internal
     *
     * @param SalesChannelRepository<CountryCollection> $countryRepository
     */
    public function __construct(
        private readonly CartService $cartService,
        private readonly SalesChannelRepository $countryRepository,
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
        if ($this->hasContextChanges($callback, $salesChannelContext)) {
            $salesChannelContext = $this->switchSalesChannelContext($callback, $salesChannelContext);

            $this->logger->debug('Shipping callback: recalculating cart with new context');
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext, false);
        } else {
            $this->logger->debug('Shipping callback: use existing cart');
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        }

        $order = $this->orderBuilder->getOrderFromCart($cart, $salesChannelContext, new RequestDataBag());
        $order->setId($callback->getId());
        $order->getPurchaseUnits()->first()?->setReferenceId((string) $callback->getPurchaseUnits()->first()?->getReferenceId());
        $order->getPurchaseUnits()->first()?->setShippingOptions($this->shippingOptionsProvider->getShippingOptions($cart, $salesChannelContext));

        if ((int) $order->getPurchaseUnits()->first()?->getShippingOptions()?->count() === 0) {
            throw ExpressShippingCallbackException::addressError($callback);
        }

        if ($error = $cart->getErrors()->filterInstance(ShippingMethodBlockedError::class)->first()) {
            /** @var ShippingMethodBlockedError $error */
            throw ExpressShippingCallbackException::methodUnavailable($callback, $error);
        }

        $this->logger->debug('Shipping callback: update order', ['order' => $order]);

        return $order;
    }

    private function hasContextChanges(OrderShippingCallback $callback, SalesChannelContext $salesChannelContext): bool
    {
        if ($callback->getShippingAddress()->getCountryCode() !== $salesChannelContext->getShippingLocation()->getCountry()->getIso()) {
            return true;
        }

        if (($shippingMethodId = $callback->getShippingOption()?->getId()) && $shippingMethodId !== $salesChannelContext->getShippingMethod()->getId()) {
            return true;
        }

        if (($state = $salesChannelContext->getShippingLocation()->getState()) && !\in_array($callback->getShippingAddress()->getAdminArea1(), [$state->getTranslation('name'), $state->getShortCode()], true)) {
            return true;
        }

        return false;
    }

    private function switchSalesChannelContext(OrderShippingCallback $callback, SalesChannelContext $salesChannelContext): SalesChannelContext
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $callback->getShippingAddress()->getCountryCode()));
        $criteria->setLimit(1);
        $criteria->getAssociation('states')
            ->addFilter(new OrFilter([
                new EqualsFilter('name', $callback->getShippingAddress()->getAdminArea1()),
                new EqualsFilter('shortCode', $callback->getShippingAddress()->getAdminArea1()),
            ]))
            ->setLimit(1);

        $country = $this->countryRepository->search($criteria, $salesChannelContext)->getEntities()->first();
        if (!$country) {
            throw ExpressShippingCallbackException::countryError($callback);
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

        return $this->salesChannelContextFactory->create($token, $salesChannelContext->getSalesChannelId(), $params);
    }
}
