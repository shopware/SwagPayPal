<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartDeleteRoute;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressCreateOrderRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\AbstractExpressPrepareCheckoutRoute;
use Swag\PayPal\Checkout\ExpressCheckout\SalesChannel\ExpressPrepareCheckoutRoute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated tag:v3.0.0 - will be removed
 */
class ExpressCheckoutController extends AbstractController
{
    /**
     * @deprecated tag:v3.0.0 - will be removed, use ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID instead
     */
    public const PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID = ExpressPrepareCheckoutRoute::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID;

    /**
     * @var AbstractCartDeleteRoute
     */
    private $cartDeleteRoute;

    /**
     * @var AbstractExpressCreateOrderRoute
     */
    private $createOrderRoute;

    /**
     * @var AbstractExpressPrepareCheckoutRoute
     */
    private $prepareCheckoutRoute;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        AbstractCartDeleteRoute $cartDeleteRoute,
        AbstractExpressCreateOrderRoute $createOrderRoute,
        AbstractExpressPrepareCheckoutRoute $prepareCheckoutRoute,
        LoggerInterface $logger
    ) {
        $this->cartDeleteRoute = $cartDeleteRoute;
        $this->createOrderRoute = $createOrderRoute;
        $this->prepareCheckoutRoute = $prepareCheckoutRoute;
        $this->logger = $logger;
    }

    /**
     * @deprecated tag:v3.0.0 - Will be removed. Use CartDeleteRoute::delete instead
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route(
     *     "/sales-channel-api/v{version}/_action/paypal/create-new-cart",
     *      name="sales-channel-api.action.paypal.create_new_cart",
     *      methods={"GET"}
     * )
     */
    public function createNewCart(SalesChannelContext $context): Response
    {
        $this->logger->error(
            'Route "sales-channel-api.action.paypal.create_new_cart" is deprecated. Use "store-api.checkout.cart.delete" instead'
        );

        return $this->cartDeleteRoute->delete($context);
    }

    /**
     * @deprecated tag:v3.0.0 - Will be removed. Use ExpressCreateOrderRoute::createPayPalOrder instead
     * @RouteScope(scopes={"sales-channel-api"})
     * @Route(
     *     "/sales-channel-api/v{version}/_action/paypal/create-payment",
     *      name="sales-channel-api.action.paypal.create_payment",
     *      methods={"GET"}
     * )
     */
    public function createPayment(SalesChannelContext $context): JsonResponse
    {
        $this->logger->error(
            'Route "sales-channel-api.action.paypal.create_payment" is deprecated. Use "store-api.paypal.express.create_order" instead'
        );

        $response = $this->createOrderRoute->createPayPalOrder($context);

        return new JsonResponse($response->getObject());
    }

    /**
     * @deprecated tag:v3.0.0 - Will be removed. Use ExpressPrepareCheckoutRoute::prepareCheckout instead
     * @RouteScope(scopes={"storefront"})
     * @Route(
     *     "/paypal/approve-payment",
     *     name="payment.paypal.approve_payment",
     *     methods={"POST"},
     *     defaults={"XmlHttpRequest"=true}
     * )
     */
    public function onApprove(SalesChannelContext $salesChannelContext, Request $request): JsonResponse
    {
        $this->logger->error(
            'Route "payment.paypal.approve_payment" is deprecated. Use "store-api.paypal.express.prepare_checkout" instead'
        );

        return new JsonResponse(
            [
                'cart_token' => $this->prepareCheckoutRoute->prepareCheckout($salesChannelContext, $request)->getToken(),
            ]
        );
    }
}
