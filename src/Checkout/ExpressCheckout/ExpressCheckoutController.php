<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\Route\AbstractExpressApprovePaymentRoute;
use Swag\PayPal\Payment\Builder\CartPaymentBuilderInterface;
use Swag\PayPal\PayPal\Api\Payment\ApplicationContext;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\PaymentResource;
use Swag\PayPal\Util\PaymentTokenExtractor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExpressCheckoutController extends AbstractController
{
    public const PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID = 'payPalEcsCartData';

    /**
     * @var CartPaymentBuilderInterface
     */
    private $cartPaymentBuilder;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var AbstractExpressApprovePaymentRoute
     */
    private $approvePaymentRoute;

    public function __construct(
        CartPaymentBuilderInterface $cartPaymentBuilder,
        CartService $cartService,
        PaymentResource $paymentResource,
        AbstractExpressApprovePaymentRoute $route
    ) {
        $this->cartPaymentBuilder = $cartPaymentBuilder;
        $this->cartService = $cartService;
        $this->paymentResource = $paymentResource;
        $this->approvePaymentRoute = $route;
    }

    /**
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route("/sales-channel-api/v{version}/_action/paypal/create-new-cart", name="sales-channel-api.action.paypal.create_new_cart", methods={"GET"})
     */
    public function createNewCart(SalesChannelContext $context): Response
    {
        $cart = $this->cartService->createNew($context->getToken());
        $this->cartService->recalculate($cart, $context);

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route("/sales-channel-api/v{version}/_action/paypal/create-payment", name="sales-channel-api.action.paypal.create_payment", methods={"GET"})
     */
    public function createPayment(SalesChannelContext $context): JsonResponse
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);
        $payment = $this->cartPaymentBuilder->getPayment(
            $cart,
            $context,
            'https://www.example.com/',
            true
        );
        $payment->getApplicationContext()->setUserAction(ApplicationContext::USER_ACTION_TYPE_CONTINUE);

        $paymentResource = $this->paymentResource->create(
            $payment,
            $context->getSalesChannel()->getId(),
            PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT
        );

        return new JsonResponse([
            'token' => PaymentTokenExtractor::extract($paymentResource),
        ]);
    }

    /**
     * @RouteScope(scopes={"storefront"})
     * @Route("/paypal/approve-payment", name="paypal.approve_payment", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function onApprove(SalesChannelContext $salesChannelContext, Request $request): JsonResponse
    {
        return new JsonResponse(
            [
                'cart_token' => $this->approvePaymentRoute->approve($salesChannelContext, $request)->getToken(),
            ]
        );
    }
}
