<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"store-api"})
 */
class ExpressPrepareCheckoutRoute extends AbstractExpressPrepareCheckoutRoute
{
    public const PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID = 'payPalEcsCartData';

    private ExpressCustomerService $expressCustomerService;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    private OrderResource $orderResource;

    private CartService $cartService;

    private LoggerInterface $logger;

    public function __construct(
        ExpressCustomerService $expressCustomerService,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
        OrderResource $orderResource,
        CartService $cartService,
        LoggerInterface $logger
    ) {
        $this->expressCustomerService = $expressCustomerService;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
        $this->orderResource = $orderResource;
        $this->cartService = $cartService;
        $this->logger = $logger;
    }

    public function getDecorated(): AbstractExpressPrepareCheckoutRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @Since("2.0.0")
     * @OA\Post(
     *     path="/store-api/paypal/express/prepare-checkout",
     *     description="Loggs in a guest customer, with the data of a paypal order",
     *     operationId="preparePayPalExpressCheckout",
     *     tags={"Store API", "PayPal"},
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="token", description="ID of the paypal order", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="The new context token"
     *    )
     * )
     *
     * @Route(
     *     "/store-api/paypal/express/prepare-checkout",
     *     name="store-api.paypal.express.prepare_checkout",
     *     methods={"POST"}
     * )
     */
    public function prepareCheckout(SalesChannelContext $salesChannelContext, Request $request): ContextTokenResponse
    {
        try {
            $this->logger->debug('Started', ['request' => $request->request->all()]);
            $paypalOrderId = $request->request->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN);

            if (!\is_string($paypalOrderId)) {
                throw new MissingRequestParameterException(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN);
            }

            $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannel()->getId());
            $newContextToken = $this->expressCustomerService->loginCustomer($paypalOrder, $salesChannelContext);

            // Since a new customer was logged in, the context changed in the system,
            // but this doesn't effect the current context given as parameter.
            // Because of that a new context for the following operations is created
            $this->logger->debug('Getting new context');
            $newSalesChannelContext = $this->salesChannelContextFactory->create(
                $newContextToken,
                $salesChannelContext->getSalesChannel()->getId()
            );

            $cart = $this->cartService->getCart($newSalesChannelContext->getToken(), $salesChannelContext);

            $expressCheckoutData = new ExpressCheckoutData($paypalOrderId);
            $cart->addExtension(self::PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID, $expressCheckoutData);

            // The cart needs to be saved.
            $this->logger->debug('Recalculating cart');
            $this->cartService->recalculate($cart, $newSalesChannelContext);

            return new ContextTokenResponse($cart->getToken());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['error' => $e, 'trace' => $e->getTraceAsString()]);

            throw $e;
        }
    }
}
