<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\AbstractPUIPaymentInstructionsRoute;
use Swag\PayPal\Checkout\PUI\SalesChannel\PUIPaymentInstructionsResponse;
use Swag\PayPal\Checkout\SalesChannel\AbstractCreateOrderRoute;
use Swag\PayPal\Checkout\SalesChannel\AbstractMethodEligibilityRoute;
use Swag\PayPal\Checkout\TokenResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 *
 * @internal
 */
class PayPalController extends StorefrontController
{
    private AbstractCreateOrderRoute $createOrderRoute;

    private AbstractMethodEligibilityRoute $methodEligibilityRoute;

    private AbstractPUIPaymentInstructionsRoute $puiPaymentInstructionsRoute;

    private AbstractExpressPrepareCheckoutRoute $expressPrepareCheckoutRoute;

    private AbstractExpressCreateOrderRoute $expressCreateOrderRoute;

    private LoggerInterface $logger;

    private AbstractContextSwitchRoute $contextSwitchRoute;

    private AbstractCartDeleteRoute $cartDeleteRoute;

    public function __construct(
        AbstractCreateOrderRoute $createOrderRoute,
        AbstractMethodEligibilityRoute $methodEligibilityRoute,
        AbstractPUIPaymentInstructionsRoute $puiPaymentInstructionsRoute,
        AbstractExpressPrepareCheckoutRoute $expressPrepareCheckoutRoute,
        AbstractExpressCreateOrderRoute $expressCreateOrderRoute,
        AbstractContextSwitchRoute $contextSwitchRoute,
        AbstractCartDeleteRoute $cartDeleteRoute,
        LoggerInterface $logger
    ) {
        $this->createOrderRoute = $createOrderRoute;
        $this->methodEligibilityRoute = $methodEligibilityRoute;
        $this->puiPaymentInstructionsRoute = $puiPaymentInstructionsRoute;
        $this->expressPrepareCheckoutRoute = $expressPrepareCheckoutRoute;
        $this->expressCreateOrderRoute = $expressCreateOrderRoute;
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->cartDeleteRoute = $cartDeleteRoute;
        $this->logger = $logger;
    }

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/create-order",
     *     name="frontend.paypal.create_order",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
    public function createOrder(SalesChannelContext $salesChannelContext, Request $request): TokenResponse
    {
        return $this->createOrderRoute->createPayPalOrder($salesChannelContext, $request);
    }

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/payment-method-eligibility",
     *     name="frontend.paypal.payment-method-eligibility",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
    public function paymentMethodEligibility(Request $request, Context $context): Response
    {
        return $this->methodEligibilityRoute->setPaymentMethodEligibility($request, $context);
    }

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/pui/payment-instructions/{transactionId}",
     *     name="frontend.paypal.pui.payment_instructions",
     *     methods={"GET"},
     *     defaults={"_loginRequired"=true, "_loginRequiredAllowGuest"=true, "XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
    public function puiPaymentInstructions(string $transactionId, SalesChannelContext $salesChannelContext): PUIPaymentInstructionsResponse
    {
        return $this->puiPaymentInstructionsRoute->getPaymentInstructions($transactionId, $salesChannelContext);
    }

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/express/prepare-checkout",
     *     name="frontend.paypal.express.prepare_checkout",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
    public function expressPrepareCheckout(Request $request, SalesChannelContext $context): ContextTokenResponse
    {
        return $this->expressPrepareCheckoutRoute->prepareCheckout($context, $request);
    }

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/express/create-order",
     *     name="frontend.paypal.express.create_order",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
    public function expressCreateOrder(SalesChannelContext $context): TokenResponse
    {
        return $this->expressCreateOrderRoute->createPayPalOrder($context);
    }

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/express/prepare-cart",
     *     name="frontend.paypal.express.prepare_cart",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
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

    /**
     * @Since("6.0.0")
     * @Route(
     *     "/paypal/error",
     *     name="frontend.paypal.error",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true, "csrf_protected"=false}
     * )
     */
    public function addErrorMessage(Request $request): Response
    {
        if ($request->request->getBoolean('cancel')) {
            $this->addFlash(self::DANGER, $this->trans('paypal.general.paymentCancel'));
            $this->logger->notice('Storefront checkout cancellation');
        } else {
            $this->addFlash(self::DANGER, $this->trans('paypal.general.paymentError'));
            $this->logger->notice('Storefront checkout error', ['error' => $request->request->get('error')]);
        }

        return new NoContentResponse();
    }
}
