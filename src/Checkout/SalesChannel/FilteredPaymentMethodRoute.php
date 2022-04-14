<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\SalesChannel;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractPaymentMethodRoute;
use Shopware\Core\Checkout\Payment\SalesChannel\PaymentMethodRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\Entity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class FilteredPaymentMethodRoute extends AbstractPaymentMethodRoute
{
    private AbstractPaymentMethodRoute $decorated;

    private PaymentMethodDataRegistry $methodDataRegistry;

    private SettingsValidationServiceInterface $settingsValidationService;

    private CartService $cartService;

    private CartPriceService $cartPriceService;

    private RequestStack $requestStack;

    public function __construct(
        AbstractPaymentMethodRoute $decorated,
        PaymentMethodDataRegistry $methodDataRegistry,
        SettingsValidationServiceInterface $settingsValidationService,
        CartService $cartService,
        CartPriceService $cartPriceService,
        RequestStack $requestStack
    ) {
        $this->decorated = $decorated;
        $this->methodDataRegistry = $methodDataRegistry;
        $this->settingsValidationService = $settingsValidationService;
        $this->cartService = $cartService;
        $this->cartPriceService = $cartPriceService;
        $this->requestStack = $requestStack;
    }

    public function getDecorated(): AbstractPaymentMethodRoute
    {
        return $this->decorated;
    }

    /**
     * @Since("6.2.0.0")
     * @Entity("payment_method")
     * @OA\Post (
     *      path="/payment-method",
     *      summary="Loads all available payment methods",
     *      operationId="readPaymentMethod",
     *      tags={"Store API", "Payment Method"},
     *      @OA\Parameter(name="Api-Basic-Parameters"),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="onlyAvailable", description="List only available", type="boolean")
     *          )
     *      ),
     *      @OA\Response(
     *          response="200",
     *          description="",
     *          @OA\JsonContent(type="object",
     *              @OA\Property(
     *                  property="total",
     *                  type="integer",
     *                  description="Total amount"
     *              ),
     *              @OA\Property(
     *                  property="aggregations",
     *                  type="object",
     *                  description="aggregation result"
     *              ),
     *              @OA\Property(
     *                  property="elements",
     *                  type="array",
     *                  @OA\Items(ref="#/components/schemas/PaymentMethod")
     *              )
     *       )
     *    )
     * )
     * @Route("/store-api/payment-method", name="store-api.payment.method", methods={"GET", "POST"})
     */
    public function load(Request $request, SalesChannelContext $context, Criteria $criteria): PaymentMethodRouteResponse
    {
        $response = $this->getDecorated()->load($request, $context, $criteria);

        if (!$request->query->getBoolean('onlyAvailable', false)) {
            return $response;
        }

        try {
            $this->settingsValidationService->validate($context->getSalesChannelId());
        } catch (PayPalSettingsInvalidException $e) {
            $this->removeAllPaymentMethods($response->getPaymentMethods());

            return $response;
        }

        if ($this->cartPriceService->isZeroValueCart($this->cartService->getCart($context->getToken(), $context))) {
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

        return $response;
    }

    private function removePaymentMethods(PaymentMethodCollection $paymentMethods, array $ids): void
    {
        foreach ($paymentMethods as $paymentMethod) {
            if (\in_array($paymentMethod->getHandlerIdentifier(), $ids, true)) {
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
}
