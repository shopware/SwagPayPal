<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Controller;

use Monolog\Level;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Framework\Api\EventListener\ErrorResponseFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\JWT\JWTException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsResponse;
use Swag\PayPal\Checkout\SalesChannel\AbstractClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractMethodEligibilityRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Storefront\Service\ReturnToken;
use Swag\PayPal\Storefront\Service\ReturnTokenService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['storefront']])]
class PayPalController extends StorefrontController
{
    public const PAYMENT_METHOD_FATAL_ERROR = 'SWAG_PAYPAL__PAYMENT_METHOD_FATAL_ERROR';

    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractCreateOrderRoute $createOrderRoute,
        private readonly AbstractMethodEligibilityRoute $methodEligibilityRoute,
        private readonly AbstractPUIPaymentInstructionsRoute $puiPaymentInstructionsRoute,
        private readonly AbstractExpressPrepareCheckoutRoute $expressPrepareCheckoutRoute,
        private readonly AbstractExpressCreateOrderRoute $expressCreateOrderRoute,
        private readonly AbstractExpressShippingCallbackRoute $expressShippingCallbackRoute,
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly AbstractCartDeleteRoute $cartDeleteRoute,
        private readonly CartService $cartService,
        private readonly CartPriceService $cartPriceService,
        private readonly AbstractClearVaultRoute $clearVaultRoute,
        private readonly LoggerInterface $logger,
        private readonly ReturnTokenService $returnTokenService,
        private readonly RouterInterface $router,
    ) {
    }

    #[Route(path: '/paypal/create-order', name: 'frontend.paypal.create_order', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false, AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true])]
    #[Route(path: '/subscription/paypal/create-order/{subscriptionToken}', name: 'frontend.subscription.paypal.create_order', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false, '_subscriptionCart' => true, '_subscriptionContext' => true, AbstractOrderBuilder::PRELIMINARY_ATTRIBUTE => true])]
    public function createOrder(SalesChannelContext $salesChannelContext, Request $request): Response
    {
        try {
            $this->addRestoreUrls($salesChannelContext, $request);

            return $this->createOrderRoute->createPayPalOrder($salesChannelContext, $request);
        } catch (PayPalApiException $e) {
            return (new ErrorResponseFactory())->getResponseFromException($e);
        }
    }

    #[Route(path: '/paypal/restore-context/{token}', name: 'frontend.paypal.restore_context', methods: ['GET'], defaults: ['csrf_protected' => false])]
    public function restoreContext(SalesChannelContext $salesChannelContext, Request $request, string $token): Response
    {
        try {
            $returnToken = $this->returnTokenService->parse($token, $salesChannelContext->getSalesChannelId());
        } catch (JWTException $e) {
            $this->logger->warning('Could not restore storefront context from return token', ['error' => $e]);

            return $this->redirectToRoute('frontend.checkout.confirm.page');
        }

        $this->returnTokenService->restoreContextToken($request, $returnToken);

        if ($returnToken->getReturnTarget() === ReturnToken::TARGET_ACCOUNT_ORDER_EDIT) {
            return $this->redirectToRoute('frontend.account.edit-order.page', [
                'orderId' => $returnToken->getOrderId(),
            ]);
        }

        return $this->redirectToRoute('frontend.checkout.confirm.page');
    }

    #[Route(path: '/paypal/payment-method-eligibility', name: 'frontend.paypal.payment-method-eligibility', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function paymentMethodEligibility(Request $request, Context $context): Response
    {
        return $this->methodEligibilityRoute->setPaymentMethodEligibility($request, $context);
    }

    #[Route(path: '/paypal/pui/payment-instructions/{transactionId}', name: 'frontend.paypal.pui.payment_instructions', methods: ['GET'], defaults: ['_loginRequired' => true, '_loginRequiredAllowGuest' => true, 'XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function puiPaymentInstructions(string $transactionId, SalesChannelContext $salesChannelContext): PUIPaymentInstructionsResponse
    {
        return $this->puiPaymentInstructionsRoute->getPaymentInstructions($transactionId, $salesChannelContext);
    }

    #[Route(path: '/paypal/express/prepare-checkout', name: 'frontend.paypal.express.prepare_checkout', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function expressPrepareCheckout(Request $request, SalesChannelContext $context): ContextTokenResponse
    {
        $affiliateCode = $request->getSession()->get(OrderService::AFFILIATE_CODE_KEY);
        $campaignCode = $request->getSession()->get(OrderService::CAMPAIGN_CODE_KEY);

        if ($affiliateCode !== null) {
            $request->request->set(OrderService::AFFILIATE_CODE_KEY, $affiliateCode);
        }

        if ($campaignCode !== null) {
            $request->request->set(OrderService::CAMPAIGN_CODE_KEY, $campaignCode);
        }

        return $this->expressPrepareCheckoutRoute->prepareCheckout($context, $request);
    }

    #[Route(path: '/paypal/express/create-order', name: 'frontend.paypal.express.create_order', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function expressCreateOrder(Request $request, SalesChannelContext $context): TokenResponse
    {
        return $this->expressCreateOrderRoute->createPayPalOrder($request, $context);
    }

    #[Route(path: '/paypal/express/prepare-cart', name: 'frontend.paypal.express.prepare_cart', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function expressPrepareCart(Request $request, SalesChannelContext $context): Response
    {
        if (!$request->request->getBoolean('deleteCart')) {
            $cart = $this->cartService->getCart($context->getToken(), $context);
            $this->cartPriceService->validateProcessable($cart, $context);
        }

        $this->contextSwitchRoute->switchContext(new RequestDataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $request->request->getAlnum('paymentMethodId') ?: $request->query->getAlnum('paymentMethodId'),
        ]), $context);

        if ($request->request->getBoolean('deleteCart')) {
            $this->cartDeleteRoute->delete($context);
        }

        return new NoContentResponse();
    }

    #[Route(path: '/paypal/express/shipping-callback/{salesChannelId}/{token}', name: 'frontend.paypal.express.shipping_callback', methods: ['POST'], defaults: ['csrf_protected' => false])]
    public function expressShippingCallback(Request $request, SalesChannelContext $context): Response
    {
        return $this->expressShippingCallbackRoute->handleCallback($request, $context);
    }

    #[Route(path: '/paypal/vault/clear', name: 'frontend.paypal.vault.clear', methods: ['GET'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function clearVault(Request $request, SalesChannelContext $context): Response
    {
        $this->clearVaultRoute->clearVault($request, $context);

        return $this->createActionResponse($request);
    }

    #[OA\Post(
        path: '/paypal/handle-error',
        operationId: 'paypalHandleError',
        description: 'Adds an error message to the flash bag',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'code', type: 'string'),
            new OA\Property(property: 'fatal', description: 'Will prevent reinitiate the corresponding payment method.', type: 'boolean', default: false),
            new OA\Property(property: 'error', type: 'string', default: null),
        ])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Error was added to the flash bag',
        )]
    )]
    #[Route(path: '/paypal/handle-error', name: 'frontend.paypal.handle-error', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function onHandleError(Request $request, SalesChannelContext $context): Response
    {
        $code = $request->request->getString('code');
        $fatal = $request->request->getBoolean('fatal');
        $isCheckout = $request->request->getBoolean('isCheckout');

        if ($isCheckout) {
            $snippetGeneric = \sprintf('paypal.error.%s', $code);
            $snippetByMethod = \sprintf('paypal.error.%s.%s', $context->getPaymentMethod()->getFormattedHandlerIdentifier(), $code);

            $transSnippetGeneric = $this->trans($snippetGeneric);
            $transSnippetByMethod = $this->trans($snippetByMethod);
            if ($transSnippetByMethod !== $snippetByMethod) {
                $this->addFlash(self::DANGER, $transSnippetByMethod);
            } elseif ($transSnippetGeneric !== $snippetGeneric) {
                $this->addFlash(self::DANGER, $transSnippetGeneric);
            } else {
                $this->addFlash(self::DANGER, $this->trans('paypal.error.SWAG_PAYPAL__GENERIC_ERROR'));
            }
        }

        if ($fatal) {
            $request->getSession()->set(self::PAYMENT_METHOD_FATAL_ERROR, $context->getPaymentMethod()->getId());
        }

        $this->logger->log(
            \in_array($code, ['SWAG_PAYPAL__SCRIPT_ERROR', 'SWAG_PAYPAL__SCRIPT_NOT_LOADED'], true) ? Level::Error : Level::Warning,
            'Storefront checkout error',
            [
                'error' => $request->request->get('error'),
                'code' => $code,
                'fatal' => $fatal,
                'paymentMethodId' => $context->getPaymentMethod()->getId(),
                'paymentMethodName' => $context->getPaymentMethod()->getName(),
            ],
        );

        return new NoContentResponse();
    }

    private function addRestoreUrls(SalesChannelContext $salesChannelContext, Request $request): void
    {
        $orderId = $request->request->getAlnum('orderId') ?: null;
        $returnTarget = $orderId !== null
            ? ReturnToken::TARGET_ACCOUNT_ORDER_EDIT
            : ReturnToken::TARGET_CHECKOUT_CONFIRM;

        $token = $this->returnTokenService->generate(
            $salesChannelContext->getToken(),
            $salesChannelContext->getSalesChannelId(),
            $returnTarget,
            $orderId,
        );

        $restoreUrl = $this->router->generate('frontend.paypal.restore_context', [
            'token' => $token,
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $request->request->set(AbstractCreateOrderRoute::RETURN_URL, $restoreUrl);
        $request->request->set(AbstractCreateOrderRoute::CANCEL_URL, $restoreUrl);
    }
}
