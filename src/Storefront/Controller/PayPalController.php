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
use Shopware\Core\Framework\Api\EventListener\ErrorResponseFactory;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\AffiliateTracking\AffiliateTrackingListener;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsResponse;
use Swag\PayPal\Checkout\SalesChannel\AbstractClearVaultRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractMethodEligibilityRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        private readonly AbstractContextSwitchRoute $contextSwitchRoute,
        private readonly AbstractCartDeleteRoute $cartDeleteRoute,
        private readonly AbstractClearVaultRoute $clearVaultRoute,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/paypal/create-order', name: 'frontend.paypal.create_order', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    #[Route(path: '/subscription/paypal/create-order/{subscriptionToken}', name: 'frontend.subscription.paypal.create_order', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false, '_subscriptionCart' => true, '_subscriptionContext' => true])]
    public function createOrder(SalesChannelContext $salesChannelContext, Request $request): Response
    {
        try {
            return $this->createOrderRoute->createPayPalOrder($salesChannelContext, $request);
        } catch (PayPalApiException $e) {
            return (new ErrorResponseFactory())->getResponseFromException($e);
        }
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
        $affiliateCode = $request->getSession()->get(AffiliateTrackingListener::AFFILIATE_CODE_KEY);
        $campaignCode = $request->getSession()->get(AffiliateTrackingListener::CAMPAIGN_CODE_KEY);

        if ($affiliateCode !== null) {
            $request->request->set(AffiliateTrackingListener::AFFILIATE_CODE_KEY, $affiliateCode);
        }

        if ($campaignCode !== null) {
            $request->request->set(AffiliateTrackingListener::CAMPAIGN_CODE_KEY, $campaignCode);
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
        $this->contextSwitchRoute->switchContext(new RequestDataBag([
            SalesChannelContextService::PAYMENT_METHOD_ID => $request->get('paymentMethodId'),
        ]), $context);

        if ($request->request->getBoolean('deleteCart')) {
            $this->cartDeleteRoute->delete($context);
        }

        return new NoContentResponse();
    }

    #[Route(path: '/paypal/vault/clear', name: 'frontend.paypal.vault.clear', methods: ['GET'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function clearVault(Request $request, SalesChannelContext $context): Response
    {
        $this->clearVaultRoute->clearVault($request, $context);

        return $this->createActionResponse($request);
    }

    /**
     * @deprecated tag:v10.0.0 - Will be removed, use {@link onHandleError} instead
     */
    #[OA\Post(
        path: '/paypal/error',
        operationId: 'paypalError',
        description: 'Adds an error message to the flash bag',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'type', type: 'string', enum: ['cancel', 'browser', 'error']),
        ])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Error was added to the flash bag',
        )]
    )]
    #[Route(path: '/paypal/error', name: 'frontend.paypal.error', methods: ['POST'], defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false])]
    public function addErrorMessage(Request $request): Response
    {
        $type = $request->request->get('type');

        if ($type === 'cancel' || $request->request->getBoolean('cancel')) {
            $this->addFlash(self::DANGER, $this->trans('paypal.general.paymentCancel'));
            $this->logger->notice('Storefront checkout cancellation', ['reason' => $request->request->get('error')]);
        } elseif ($type === 'browser') {
            $this->addFlash(self::DANGER, $this->trans('paypal.general.browserUnsupported'));
            $this->logger->notice('Storefront checkout browser unsupported', ['error' => $request->request->get('error')]);
        } else {
            $this->addFlash(self::DANGER, $this->trans('paypal.general.paymentError'));
            $this->logger->notice('Storefront checkout error', ['error' => $request->request->get('error')]);
        }

        return new NoContentResponse();
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

        // Simply add a snippet for the error code to create a flash
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

        if ($fatal) {
            $request->getSession()->set(self::PAYMENT_METHOD_FATAL_ERROR, $context->getPaymentMethod()->getId());
        }

        $this->logger->warning('Storefront checkout error', [
            'error' => $request->request->get('error'),
            'code' => $code,
            'fatal' => $fatal,
            'paymentMethodId' => $context->getPaymentMethod()->getId(),
            'paymentMethodName' => $context->getPaymentMethod()->getName(),
        ]);

        return new NoContentResponse();
    }
}
