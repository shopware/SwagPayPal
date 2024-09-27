<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\RoutingException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\ContextTokenResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressCheckoutData;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressCustomerService;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ExpressPrepareCheckoutRoute extends AbstractExpressPrepareCheckoutRoute
{
    public const PAYPAL_EXPRESS_CHECKOUT_CART_EXTENSION_ID = 'payPalEcsCartData';

    /**
     * @internal
     */
    public function __construct(
        private readonly ExpressCustomerService $expressCustomerService,
        private readonly AbstractSalesChannelContextFactory $salesChannelContextFactory,
        private readonly OrderResource $orderResource,
        private readonly CartService $cartService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getDecorated(): AbstractExpressPrepareCheckoutRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/store-api/paypal/express/prepare-checkout',
        operationId: 'preparePayPalExpressCheckout',
        description: 'Logs in a guest customer, with the data of a paypal order',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [new OA\Property(
            property: 'token',
            description: 'ID of the paypal order',
            type: 'string'
        )])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'The url to redirect to',
            content: new OA\JsonContent(properties: [new OA\Property(
                property: 'redirectUrl',
                type: 'string'
            )])
        )],
    )]
    #[Route(path: '/store-api/paypal/express/prepare-checkout', name: 'store-api.paypal.express.prepare_checkout', methods: ['POST'])]
    public function prepareCheckout(SalesChannelContext $salesChannelContext, Request $request): ContextTokenResponse
    {
        try {
            $this->logger->debug('Started', ['request' => $request->request->all()]);
            $paypalOrderId = $request->request->get(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN);

            if (!\is_string($paypalOrderId)) {
                throw RoutingException::missingRequestParameter(PayPalPaymentHandler::PAYPAL_REQUEST_PARAMETER_TOKEN);
            }

            $paypalOrder = $this->orderResource->get($paypalOrderId, $salesChannelContext->getSalesChannel()->getId());
            $newContextToken = $this->expressCustomerService->loginCustomer($paypalOrder, $salesChannelContext, new RequestDataBag($request->request->all()));

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
