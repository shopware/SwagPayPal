<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Controller;

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
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Swag\PayPal\Checkout\Cart\Service\CartPriceService;
use Swag\PayPal\Checkout\Exception\MissingCountryIdException;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressShippingCallbackRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsResponse;
use Swag\PayPal\Checkout\SalesChannel\AbstractClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractClientTokenRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractMethodEligibilityRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Storefront\BrowserErrorCode;
use Swag\PayPal\Storefront\Data\Struct\AbstractScriptData;
use Swag\PayPal\Storefront\Service\ReturnToken;
use Swag\PayPal\Storefront\Service\ReturnTokenService;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
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
        private readonly AbstractClientTokenRoute $clientTokenRoute,
        private readonly LoggerInterface $logger,
        private readonly ReturnTokenService $returnTokenService,
        private readonly RouterInterface $router,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }

    #[Route(path: '/paypal/client-token', name: 'frontend.paypal.client_token', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function clientToken(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        return $this->clientTokenRoute->getClientToken($request, $salesChannelContext);
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
            $this->logger->notice(
                'Could not restore the buyer\'s session after the return from PayPal, because the return token is invalid or expired',
                Log::context(code: LogCode::STOREFRONT_RETURN_TOKEN_INVALID, exception: $e),
            );

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
    public function expressPrepareCheckout(Request $request, SalesChannelContext $context): ContextTokenResponse|Response
    {
        $affiliateCode = $request->getSession()->get(OrderService::AFFILIATE_CODE_KEY);
        $campaignCode = $request->getSession()->get(OrderService::CAMPAIGN_CODE_KEY);

        if ($affiliateCode !== null) {
            $request->request->set(OrderService::AFFILIATE_CODE_KEY, $affiliateCode);
        }

        if ($campaignCode !== null) {
            $request->request->set(OrderService::CAMPAIGN_CODE_KEY, $campaignCode);
        }

        try {
            return $this->expressPrepareCheckoutRoute->prepareCheckout($context, $request);
        } catch (PayPalApiException|MissingCountryIdException $e) {
            return (new ErrorResponseFactory())->getResponseFromException($e);
        }
    }

    #[Route(path: '/paypal/express/create-order', name: 'frontend.paypal.express.create_order', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function expressCreateOrder(Request $request, SalesChannelContext $context): TokenResponse
    {
        // Same targets as the popup's `cancelRedirectUrl`
        $cancelUrl = $this->router->generate(
            $request->query->getBoolean('addProductToCart') ? 'frontend.checkout.cart.page' : 'frontend.checkout.register.page',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
        $request->request->set(AbstractCreateOrderRoute::RETURN_URL, $cancelUrl);
        $request->request->set(AbstractCreateOrderRoute::CANCEL_URL, $cancelUrl);

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

    #[Route(path: '/paypal/vault/clear', name: 'frontend.paypal.vault.clear', methods: ['GET', 'POST'], defaults: ['_loginRequired' => true, 'XmlHttpRequest' => true, 'csrf_protected' => false])]
    #[Route(path: '/subscription/paypal/vault/clear/{subscriptionToken}', name: 'frontend.subscription.paypal.vault.clear', methods: ['POST'], defaults: ['_loginRequired' => true, 'XmlHttpRequest' => true, 'csrf_protected' => false, '_subscriptionCart' => true, '_subscriptionContext' => true])]
    public function clearVault(Request $request, SalesChannelContext $context): Response
    {
        // @deprecated tag:v11.0.0 - GET requests will be rejected, use POST instead
        if (!$request->isMethod(Request::METHOD_POST) && !$this->isRequestFromShop($request)) {
            return $this->createActionResponse($request);
        }

        $this->clearVaultRoute->clearVault($request, $context);

        return $this->createActionResponse($request);
    }

    #[OA\Post(
        path: '/paypal/handle-error',
        operationId: 'paypalHandleError',
        description: 'Adds an error message to the flash bag',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'step', type: 'string'),
            new OA\Property(property: 'pageType', type: 'string', description: 'Page type "checkout" will prevent any flash messages added', enum: [
                AbstractScriptData::PAGE_TYPE_CART,
                AbstractScriptData::PAGE_TYPE_CHECKOUT,
                AbstractScriptData::PAGE_TYPE_HOME,
                AbstractScriptData::PAGE_TYPE_MINI_CART,
                AbstractScriptData::PAGE_TYPE_PRODUCT_DETAILS,
                AbstractScriptData::PAGE_TYPE_PRODUCT_LISTING,
                AbstractScriptData::PAGE_TYPE_SEARCH_RESULTS,
            ]),
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
        $step = \mb_trim($request->request->getString('step')) ?: null;
        $code = \mb_trim($request->request->getString('code')) ?: null;
        $pageType = \mb_trim($request->request->getString('pageType')) ?: null;
        $fatal = $request->request->getBoolean('fatal');
        $error = $request->request->getString('error') ?: null;

        /** @deprecated tag:v11.0.0 - Will be removed */
        $isCheckout = $request->request->getBoolean('isCheckout');

        // we are either on the checkout page or in a js plugin implementing payment.
        // In both cases any submission error should be shown to the customer
        if ($pageType === AbstractScriptData::PAGE_TYPE_CHECKOUT || $step === 'SUBMIT_FLOW' || $isCheckout) {
            $snippetGeneric = \sprintf('paypal.error.%s', $code);
            $snippetByMethod = \sprintf('paypal.error.%s.%s', $context->getPaymentMethod()->getFormattedHandlerIdentifier(), $code);

            $transSnippetGeneric = $this->trans($snippetGeneric);
            $transSnippetByMethod = $this->trans($snippetByMethod);
            if ($transSnippetByMethod !== $snippetByMethod) {
                $this->addFlash(self::DANGER, $transSnippetByMethod);
            } elseif ($transSnippetGeneric !== $snippetGeneric) {
                $this->addFlash(self::DANGER, $transSnippetGeneric);
            } else {
                $this->addFlash(self::DANGER, $this->trans('paypal.error.' . BrowserErrorCode::GENERIC));
            }
        }

        // session key is only read in checkout, if the pageType isn't checkout too
        // its a JS plugin rendering aside the main one (like pay-later)
        $isV6Enabled = $this->systemConfigService->getBool(Settings::SDK_V6_ENABLED, $context->getSalesChannelId());
        if ($fatal && (!$isV6Enabled || $pageType === AbstractScriptData::PAGE_TYPE_CHECKOUT)) {
            $request->getSession()->set(self::PAYMENT_METHOD_FATAL_ERROR, $context->getPaymentMethod()->getId());
        }

        $this->logBrowserError($context, $code, $step, $pageType, $fatal, $error);

        return new NoContentResponse();
    }

    private function logBrowserError(SalesChannelContext $context, ?string $code, ?string $step, ?string $pageType, bool $fatal, ?string $error): void
    {
        // untrusted browser input, `Log::context()` cuts it
        $logContext = [
            Log::PAYMENT_METHOD => $context->getPaymentMethod()->getTechnicalName(),
            Log::CLIENT_CODE => $code,
            Log::CLIENT_STEP => $step,
            Log::PAGE_TYPE => $pageType,
            Log::CLIENT_ERROR => $error,
            Log::FATAL => $fatal,
        ];

        $isScriptLoad = $code === BrowserErrorCode::GENERIC && $step === BrowserErrorCode::STEP_SCRIPT_LOAD;

        switch ($isScriptLoad ? BrowserErrorCode::SCRIPT : $code) {
            case BrowserErrorCode::USER_CANCELLED:
            case BrowserErrorCode::EXPRESS_USER_CANCELLED:
                $this->logger->notice('The browser reported that the buyer closed the PayPal window', Log::context(...$logContext, code: LogCode::STOREFRONT_BUYER_CANCELLED));

                break;
            case BrowserErrorCode::DECLINED:
                $this->logger->notice('The browser reported that PayPal declined the payment method', Log::context(...$logContext, code: LogCode::STOREFRONT_PAYMENT_DECLINED));

                break;
            case BrowserErrorCode::SCRIPT:
            case BrowserErrorCode::SCRIPT_NOT_LOADED:
                $this->logger->notice('The browser reported that the PayPal script failed', Log::context(...$logContext, code: LogCode::STOREFRONT_SCRIPT_FAILED));

                break;
            case BrowserErrorCode::BROWSER_UNSUPPORTED:
            case BrowserErrorCode::NOT_ELIGIBLE:
                $this->logger->debug('The browser reported that PayPal is unavailable for this buyer', Log::context(...$logContext));

                break;
            default:
                $this->logger->notice('The browser reported a PayPal error', Log::context(...$logContext, code: LogCode::STOREFRONT_ERROR_REPORTED));
        }
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

    /**
     * Browsers send the session cookie along with GET navigations from other websites
     */
    private function isRequestFromShop(Request $request): bool
    {
        $fetchSite = $request->headers->get('Sec-Fetch-Site');
        if ($fetchSite !== null) {
            return $fetchSite === 'same-origin';
        }

        // older browsers, and all browsers on shops without HTTPS, send no fetch metadata, but the page of the link as referer
        $referer = $request->headers->get('referer');

        return $referer !== null && \parse_url($referer, \PHP_URL_HOST) === $request->getHost();
    }
}
