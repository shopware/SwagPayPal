<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Plus;

use Shopware\Core\Checkout\Payment\SalesChannel\HandlePaymentMethodRouteResponse;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\AccountOrderController;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @deprecated tag:v10.0.0 - Will be removed without replacement.
 *
 * @internal
 */
#[Package('checkout')]
class PlusPaymentHandleController extends StorefrontController
{
    private AbstractContextSwitchRoute $contextSwitchRoute;

    private AccountOrderController $accountOrderController;

    private CheckoutController $checkoutController;

    private RequestStack $requestStack;

    /**
     * @internal
     */
    public function __construct(
        AbstractContextSwitchRoute $contextSwitchRoute,
        AccountOrderController $accountOrderController,
        CheckoutController $checkoutController,
        RequestStack $requestStack,
    ) {
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->accountOrderController = $accountOrderController;
        $this->checkoutController = $checkoutController;
        $this->requestStack = $requestStack;
    }

    #[Route(path: '/paypal/plus/payment/handle', name: 'frontend.paypal.plus.handle', methods: ['POST'], defaults: ['XmlHttpRequest' => true, '_routeScope' => ['storefront']])]
    public function handlePlusPayment(Request $request, SalesChannelContext $context): HandlePaymentMethodRouteResponse
    {
        $this->contextSwitchRoute->switchContext(
            new RequestDataBag([SalesChannelContextService::LANGUAGE_ID => $request->request->getAlnum('languageId')]),
            $context
        );

        $orderId = $request->request->getAlnum('orderId');
        if ($orderId) {
            $response = $this->accountOrderController->updateOrder($orderId, $request, $context);
        } else {
            $response = $this->checkoutController->order(new RequestDataBag($request->request->all()), $context, $request);
        }

        if ($response instanceof RedirectResponse) {
            return new HandlePaymentMethodRouteResponse($response);
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return new HandlePaymentMethodRouteResponse($this->redirectToRoute('frontend.checkout.confirm.page'));
        }

        if ($orderId) {
            return new HandlePaymentMethodRouteResponse($this->redirectToRoute('frontend.checkout.finish.page', ['orderId' => $orderId, 'changedPayment' => false, 'paymentFailed' => true]));
        }

        return new HandlePaymentMethodRouteResponse($this->redirect($request->getRequestUri()));
    }
}
