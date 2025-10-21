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
    #[Route(path: '/store-api/paypal/express/shipping-callback', name: 'store-api.paypal.express.shipping_callback', methods: ['POST'], defaults: ['csrf_protected' => false])]
    public function handleCallback(Request $request, SalesChannelContext $salesChannelContext): JsonResponse
    {
        $data = $request->toArray();

        $this->logger->info('PayPal shipping callback received', [
            'paypalOrderId' => $data['id'] ?? null,
            'shippingAddress' => $data['shipping_address'] ?? null,
        ]);

        $paypalOrderId = $data['id'] ?? null;
        $shippingAddress = $data['shipping_address'] ?? null;

        if (!\is_string($paypalOrderId) || !\is_array($shippingAddress)) {
            $this->logger->error('Invalid callback payload', [
                'payload' => $data,
            ]);

            return new JsonResponse(['error' => 'Invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $updatedPurchaseUnits = $this->shippingCallbackService->recalculateCart(
                $paypalOrderId,
                $shippingAddress,
                $salesChannelContext
            );

            $this->logger->info('PayPal shipping callback processed successfully', [
                'paypalOrderId' => $paypalOrderId,
                'updatedAmount' => $updatedPurchaseUnits[0]['amount']['value'] ?? null,
            ]);

            return new JsonResponse(['purchase_units' => $updatedPurchaseUnits]);
        } catch (\Throwable $e) {
            $this->logger->error('PayPal shipping callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse(
                ['error' => 'Failed to process shipping callback'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
