<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PaymentsApi\Administration;

use OpenApi\Attributes as OA;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V1\Capture;
use Shopware\PayPalSDK\Struct\V1\Payment;
use Shopware\PayPalSDK\Struct\V1\Payment\Transaction\RelatedResource;
use Swag\PayPal\PaymentsApi\Administration\Exception\PaymentNotFoundException;
use Swag\PayPal\PaymentsApi\Administration\Exception\RequiredParameterInvalidException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V1\Resource\CaptureResource;
use Swag\PayPal\RestApi\V1\Resource\OrdersResource;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\RestApi\V1\Resource\SaleResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class PayPalPaymentController extends AbstractController
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PaymentResource $paymentResource,
        private readonly SaleResource $saleResource,
        private readonly AuthorizationResource $authorizationResource,
        private readonly OrdersResource $ordersResource,
        private readonly CaptureResource $captureResource,
        private readonly EntityRepository $orderRepository,
    ) {
    }

    #[OA\Get(
        path: '/paypal/payment-details/{orderId}/{paymentId}',
        operationId: 'paymentDetails',
        description: 'Loads the Payment details of the given PayPal ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderId',
                name: 'orderId',
                description: 'ID of the order which contains the PayPal payment',
                in: 'path',
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'paymentId',
                name: 'paymentId',
                description: 'ID of the PayPal payment',
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal payment',
            content: new OA\JsonContent(ref: Payment::class)
        )]
    )]
    #[Route(path: '/api/paypal/payment-details/{orderId}/{paymentId}', name: 'api.paypal.payment_details', defaults: ['_acl' => ['order.viewer']], methods: ['GET'])]
    public function paymentDetails(string $orderId, string $paymentId, Context $context): JsonResponse
    {
        try {
            $payment = $this->paymentResource->get($paymentId, $this->getSalesChannelIdByOrderId($orderId, $context));
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() === Response::HTTP_NOT_FOUND) {
                throw new PaymentNotFoundException($paymentId);
            }

            throw $e;
        }

        return new JsonResponse($payment);
    }

    #[OA\Get(
        path: '/paypal/resource-details/{resourceType}/{resourceId}/{orderId}',
        operationId: 'resourceDetails',
        description: 'Loads the PayPal resource details of the given resource ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'resourceType',
                name: 'resourceType',
                description: 'Type of the resource. Possible values: sale, authorization, order, capture, refund',
                in: 'path',
                schema: new OA\Schema(type: 'string', enum: [RelatedResource::SALE, RelatedResource::AUTHORIZE, RelatedResource::ORDER, RelatedResource::CAPTURE])
            ),
            new OA\Parameter(
                parameter: 'resourceId',
                name: 'resourceId',
                description: 'ID of the PayPal resource',
                in: 'path',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                parameter: 'orderId',
                name: 'orderId',
                description: 'ID of the order which contains the PayPal resource',
                in: 'path',
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal resource',
            content: new OA\JsonContent(oneOf: [
                new OA\Schema(ref: RelatedResource\Sale::class),
                new OA\Schema(ref: RelatedResource\Authorization::class),
                new OA\Schema(ref: RelatedResource\Order::class),
                new OA\Schema(ref: Capture::class),
            ])
        )]
    )]
    #[Route(path: '/api/paypal/resource-details/{resourceType}/{resourceId}/{orderId}', name: 'api.paypal.resource_details', defaults: ['_acl' => ['order.viewer']], methods: ['GET'])]
    public function resourceDetails(Context $context, string $resourceType, string $resourceId, string $orderId): JsonResponse
    {
        $salesChannelId = $this->getSalesChannelIdByOrderId($orderId, $context);
        switch ($resourceType) {
            case RelatedResource::SALE:
                $resource = $this->saleResource->get($resourceId, $salesChannelId);

                break;
            case RelatedResource::AUTHORIZE:
                $resource = $this->authorizationResource->get($resourceId, $salesChannelId);

                break;
            case RelatedResource::ORDER:
                $resource = $this->ordersResource->get($resourceId, $salesChannelId);

                break;
            case RelatedResource::CAPTURE:
                $resource = $this->captureResource->get($resourceId, $salesChannelId);

                break;
            default:
                throw new RequiredParameterInvalidException('resourceType');
        }

        return new JsonResponse($resource);
    }

    private function getSalesChannelIdByOrderId(string $orderId, Context $context): string
    {
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search(new Criteria([$orderId]), $context)->first();

        if ($order === null) {
            throw OrderException::orderNotFound($orderId);
        }

        return $order->getSalesChannelId();
    }
}
