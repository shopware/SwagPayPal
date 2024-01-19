<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Annotations as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\TokenResponse;
use Swag\PayPal\OrdersApi\Builder\OrderFromCartBuilder;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order\ApplicationContext;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ExpressCreateOrderRoute extends AbstractExpressCreateOrderRoute
{
    private CartService $cartService;

    private OrderFromCartBuilder $orderFromCartBuilder;

    private OrderResource $orderResource;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        CartService $cartService,
        OrderFromCartBuilder $orderFromCartBuilder,
        OrderResource $orderResource,
        LoggerInterface $logger
    ) {
        $this->cartService = $cartService;
        $this->orderFromCartBuilder = $orderFromCartBuilder;
        $this->orderResource = $orderResource;
        $this->logger = $logger;
    }

    public function getDecorated(): AbstractExpressCreateOrderRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Post(
     *     path="/store-api/paypal/express/create-order",
     *     description="Creates a PayPal order from the existing cart",
     *     operationId="createPayPalExpressOrder",
     *     tags={"Store API", "PayPal"},
     *
     *     @OA\Response(
     *         response="200",
     *         description="The new token of the order"
     *    )
     * )
     */
    #[Route(path: '/store-api/paypal/express/create-order', name: 'store-api.paypal.express.create_order', methods: ['POST'])]
    public function createPayPalOrder(Request $request, SalesChannelContext $salesChannelContext): TokenResponse
    {
        try {
            $this->logger->debug('Started');
            $cart = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
            $this->logger->debug('Building order');
            $order = $this->orderFromCartBuilder->getOrder($cart, $request, $salesChannelContext, null);
            $order->getPaymentSource()?->getPaypal()?->getExperienceContext()->setShippingPreference(ApplicationContext::SHIPPING_PREFERENCE_GET_FROM_FILE);
            $order->getPaymentSource()?->getPaypal()?->getExperienceContext()->setUserAction(ApplicationContext::USER_ACTION_CONTINUE);

            $orderResponse = $this->orderResource->create(
                $order,
                $salesChannelContext->getSalesChannel()->getId(),
                PartnerAttributionId::PAYPAL_EXPRESS_CHECKOUT
            );

            return new TokenResponse($orderResponse->getId());
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage(), ['error' => $e]);

            throw $e;
        }
    }
}
