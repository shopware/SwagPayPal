<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\SalesChannel;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\PayPalSDK\Struct\V2\OrderShippingCallback;
use Swag\PayPal\Checkout\ExpressCheckout\Service\ExpressShippingCallbackService;
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
    ) {
    }

    public function getDecorated(): AbstractExpressShippingCallbackRoute
    {
        throw new DecorationPatternException(self::class);
    }

    #[OA\Post(
        path: '/paypal/express/shipping-callback',
        operationId: 'handlePayPalExpressShippingCallback',
        description: 'Handles PayPal shipping address change callbacks and returns updated cart pricing',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'id', description: 'PayPal order ID', type: 'string'),
            new OA\Property(property: 'shipping_address', type: 'object'),
            new OA\Property(property: 'purchase_units', type: 'array', items: new OA\Items(type: 'object')),
        ])),
        tags: ['Store API', 'PayPal'],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Updated purchase units with recalculated prices',
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'purchase_units', type: 'array', items: new OA\Items(type: 'object')),
            ])
        )],
    )]
    #[Route(path: '/store-api/paypal/express/shipping-callback/{salesChannelId}/{token}', name: 'store-api.paypal.express.shipping_callback', methods: ['POST'], defaults: ['csrf_protected' => false, 'auth_required' => false])]
    public function handleCallback(Request $request, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $callback = (new OrderShippingCallback())->assign($request->request->all());

        $this->logger->debug('Shipping callback received', ['callback' => $callback]);

        if (!$callback->isset('id') || !$callback->isset('shippingAddress') || !$callback->isset('purchaseUnits')) {
            $this->logger->error('Shipping callback: Invalid payload', ['callback' => $callback]);

            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->shippingCallbackService->recalculateCart($callback, $salesChannelContext);

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            $this->logger->error('Shipping callback failed', ['exception' => $e]);

            if ($e instanceof HttpException) {
                throw $e;
            }

            return new JsonResponse(
                ['error' => 'Failed to process shipping callback'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
