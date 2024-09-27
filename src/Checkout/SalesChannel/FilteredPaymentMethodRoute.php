<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
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
    private AbstractPaymentMethodRoute $decorated;

    private PaymentMethodDataRegistry $methodDataRegistry;

    private SettingsValidationServiceInterface $settingsValidationService;

    private CartService $cartService;

    private CartPriceService $cartPriceService;

    private RequestStack $requestStack;

    private ExcludedProductValidator $excludedProductValidator;

    private AvailabilityService $availabilityService;

    private EntityRepository $orderRepository;

    /**
     * @internal
     */
    public function __construct(
        AbstractPaymentMethodRoute $decorated,
        PaymentMethodDataRegistry $methodDataRegistry,
        SettingsValidationServiceInterface $settingsValidationService,
        CartService $cartService,
        CartPriceService $cartPriceService,
        ExcludedProductValidator $excludedProductValidator,
        RequestStack $requestStack,
        AvailabilityService $availabilityService,
        EntityRepository $orderRepository,
    ) {
        $this->decorated = $decorated;
        $this->methodDataRegistry = $methodDataRegistry;
        $this->settingsValidationService = $settingsValidationService;
        $this->cartService = $cartService;
        $this->cartPriceService = $cartPriceService;
        $this->excludedProductValidator = $excludedProductValidator;
        $this->requestStack = $requestStack;
        $this->availabilityService = $availabilityService;
        $this->orderRepository = $orderRepository;
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
        } catch (PayPalSettingsInvalidException $e) {
            $this->removeAllPaymentMethods($response->getPaymentMethods());

            return $response;
        }
        $cart = $this->cartService->getCart($context->getToken(), $context);
        if ($this->cartPriceService->isZeroValueCart($cart)) {
            $this->removeAllPaymentMethods($response->getPaymentMethods());

            return $response;
        }

        if ($this->excludedProductValidator->cartContainsExcludedProduct($cart, $context)) {
            $this->removeAllPaymentMethods($response->getPaymentMethods());

            return $response;
        }

        try {
            $ineligiblePaymentMethods = $this->requestStack->getSession()->get(MethodEligibilityRoute::SESSION_KEY);
            if (\is_array($ineligiblePaymentMethods)) {
                $this->removePaymentMethods($response->getPaymentMethods(), $ineligiblePaymentMethods);
            }
        } catch (SessionNotFoundException $e) {
        }

        $order = $this->checkOrder($request, $context->getContext());
        $this->removePaymentMethods(
            $response->getPaymentMethods(),
            $order
                ? $this->availabilityService->filterPaymentMethodsByOrder($response->getPaymentMethods(), $cart, $order, $context)
                : $this->availabilityService->filterPaymentMethods($response->getPaymentMethods(), $cart, $context)
        );

        return $response;
    }

    /**
     * @param string[] $handlers
     */
    private function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $handlers): void
    {
        foreach ($paymentMethods as $paymentMethod) {
            if (\in_array($paymentMethod->getHandlerIdentifier(), $handlers, true)) {
                $paymentMethods->remove($paymentMethod->getId());
            }
        }
    }

    private function removeAllPaymentMethods(PaymentMethodCollection $paymentMethods): void
    {
        foreach ($paymentMethods as $paymentMethod) {
            if ($this->methodDataRegistry->isPayPalPaymentMethod($paymentMethod)) {
                $paymentMethods->remove($paymentMethod->getId());
            }
        }
    }

    private function checkOrder(Request $request, Context $context): ?OrderEntity
    {
        $orderId = $request->attributes->getAlnum('orderId') ?: $this->requestStack->getCurrentRequest()?->attributes->getAlnum('orderId');
        if (!$orderId) {
            return null;
        }

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems');
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $context)->first();

        return $order;
    }
}
