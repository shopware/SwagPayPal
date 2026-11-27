<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\OrderShippingCallback;
use Swag\PayPal\Checkout\ExpressCheckout\ExpressShippingCallbackException;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\CallbackOutcome;
use Swag\PayPal\Diagnostics\ExpressCallback\Recording\LastCallbackStorage;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['store-api']])]
class ExpressShippingCallbackRoute extends AbstractExpressShippingCallbackRoute
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ExpressShippingCallbackService $shippingCallbackService,
        private readonly LoggerInterface $logger,
        private readonly LastCallbackStorage $lastCallbackStorage,
    ) {
    }

    public function getDecorated(): AbstractExpressShippingCallbackRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/paypal/express/shipping-callback/{salesChannelId}/{token}',
        operationId: 'handlePayPalExpressShippingCallback',
        description: 'Handles PayPal shipping address change callbacks and returns updated cart pricing',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(ref: OrderShippingCallback::class)),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Updated order with recalculated prices',
            content: new OA\JsonContent(ref: Order::class)
        )],
    )]
    #[Route(path: '/store-api/paypal/express/shipping-callback/{salesChannelId}/{token}', name: 'store-api.paypal.express.shipping_callback', methods: ['POST'], defaults: ['csrf_protected' => false, 'auth_required' => false])]
    public function handleCallback(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $callback = (new OrderShippingCallback())->assign($request->request->all());

        if (!$callback->isset('id') || !$callback->isset('shippingAddress') || !$callback->isset('purchaseUnits')) {
            $response = new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);

            // the reachability test sends no payload
            if (ReachabilityProbe::isProbe($request)) {
                return ReachabilityProbe::answer($response);
            }

            $this->logger->notice('Rejected a shipping callback with an invalid payload', Log::context(
                code: LogCode::EXPRESS_CALLBACK_INVALID,
                salesChannelId: $salesChannelContext->getSalesChannelId(),
            ));

            return $response;
        }

        try {
            $order = $this->shippingCallbackService->recalculateCart($callback, $salesChannelContext);
            $this->lastCallbackStorage->record($salesChannelContext->getSalesChannelId(), CallbackOutcome::Answered);

            return new JsonResponse($order);
        } catch (\Throwable $e) {
            $logContext = [
                Log::SALES_CHANNEL_ID => $salesChannelContext->getSalesChannelId(),
                Log::PAYPAL_ORDER_ID => $callback->getId(),
                Log::EXCEPTION => $e,
            ];

            if ($e instanceof ExpressShippingCallbackException) {
                $this->logger->notice('Could not offer shipping for PayPal order {paypalOrderId}: {errorCode}', Log::context(
                    ...$logContext,
                    code: LogCode::EXPRESS_CALLBACK_SHIPPING_UNAVAILABLE,
                    countryCode: $callback->getShippingAddress()->getCountryCode(),
                ));
                $this->lastCallbackStorage->record($salesChannelContext->getSalesChannelId(), CallbackOutcome::Rejected);

                return $e->intoCallbackResponse();
            }

            $this->logger->error('Could not answer the shipping callback for PayPal order {paypalOrderId}, PayPal shows the buyer an error', Log::context(
                ...$logContext,
                code: LogCode::EXPRESS_CALLBACK_FAILED,
            ));
            $this->lastCallbackStorage->record($salesChannelContext->getSalesChannelId(), CallbackOutcome::Failed);

            throw $e;
        }
    }
}
