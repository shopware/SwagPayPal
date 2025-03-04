<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderCollection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Cart\Service\ExcludedProductValidator;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Util\Availability\AvailabilityService;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class FilteredPaymentMethodRoute extends AbstractPaymentMethodRoute
{
    /**
     * @internal
     *
     * @param EntityRepository<OrderCollection> $orderRepository
     */
    public function __construct(
        private readonly AbstractPaymentMethodRoute $decorated,
        private readonly PaymentMethodDataRegistry $methodDataRegistry,
        private readonly SettingsValidationServiceInterface $settingsValidationService,
        private readonly CartService $cartService,
        private readonly CartPriceService $cartPriceService,
        private readonly ExcludedProductValidator $excludedProductValidator,
        private readonly RequestStack $requestStack,
        private readonly AvailabilityService $availabilityService,
        private readonly EntityRepository $orderRepository,
    ) {
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this->decorated;
    }

    #[Route(path: '/store-api/payment-method', name: 'store-api.payment.method', defaults: ['_entity' => 'payment_method'], methods: ['GET', 'POST'])]
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $response = $this->getDecorated()->load($request, $context, $criteria);

        if (!$request->query->getBoolean('onlyAvailable') && !$request->request->getBoolean('onlyAvailable')) {
            return $response;
        }

        try {
            $this->settingsValidationService->validate($context->getSalesChannelId());
        } catch (PayPalSettingsInvalidException) {
            return $this->removeAllPaymentMethods($response);
        }

        $cart = $this->cartService->getCart($context->getToken(), $context);
        if ($this->cartPriceService->isZeroValueCart($cart)) {
            return $this->removeAllPaymentMethods($response);
        }

        if ($this->excludedProductValidator->cartContainsExcludedProduct($cart, $context)) {
            return $this->removeAllPaymentMethods($response);
        }

        try {
            $ineligiblePaymentMethods = $this->requestStack->getSession()->get(MethodEligibilityRoute::SESSION_KEY);

            if (\is_array($ineligiblePaymentMethods)) {
                $response = $this->removePaymentMethods($response, $ineligiblePaymentMethods);
            }
        } catch (SessionNotFoundException) {
        }

        $order = $this->checkOrder($request, $context->getContext());

        return $this->removePaymentMethods(
            $response,
            $order
                ? $this->availabilityService->filterPaymentMethodsByOrder($response->getPaymentMethods(), $cart, $order, $context)
                : $this->availabilityService->filterPaymentMethods($response->getPaymentMethods(), $cart, $context)
        );
    }

    /**
     * @param string[] $handlers
     */
    private function removePaymentMethods(PaymentMethodRouteResponse $response, array $handlers): PaymentMethodRouteResponse
    {
        $paymentMethods = $response->getPaymentMethods()->filter(static function (PaymentMethodEntity $entity) use ($handlers) {
            return !\in_array($entity->getHandlerIdentifier(), $handlers, true);
        });

        return $this->updateResponse($response, $paymentMethods);
    }

    private function removeAllPaymentMethods(PaymentMethodRouteResponse $response): PaymentMethodRouteResponse
    {
        $paymentMethods = $response->getPaymentMethods()->filter(function (PaymentMethodEntity $entity) {
            return !$this->methodDataRegistry->isPayPalPaymentMethod($entity);
        });

        return $this->updateResponse($response, $paymentMethods);
    }

    private function checkOrder(Request $request, Context $context): ?OrderEntity
    {
        $orderId = $request->attributes->getAlnum('orderId') ?: $this->requestStack->getCurrentRequest()?->attributes->getAlnum('orderId');
        if (!$orderId) {
            return null;
        }

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');

        return $this->orderRepository->search($criteria, $context)->first();
    }

    private function updateResponse(PaymentMethodRouteResponse $response, PaymentMethodCollection $paymentMethods): PaymentMethodRouteResponse
    {
        $response->getObject()->assign([
            'entities' => $paymentMethods,
            'elements' => $paymentMethods->getElements(),
            'total' => $paymentMethods->count(),
        ]);

        return $response;
    }
}
