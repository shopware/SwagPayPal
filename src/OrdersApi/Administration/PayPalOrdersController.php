<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Administration;

use OpenApi\Attributes as OA;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\OrdersApi\Administration\Exception\OrderNotFoundException;
use Swag\PayPal\OrdersApi\Administration\Service\CaptureRefundCreator;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V2\Resource\CaptureResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\RestApi\V2\Resource\RefundResource;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class PayPalOrdersController extends AbstractController
{
    public const REQUEST_PARAMETER_CURRENCY = 'currency';
    public const REQUEST_PARAMETER_AMOUNT = 'amount';
    public const REQUEST_PARAMETER_INVOICE_NUMBER = 'invoiceNumber';
    public const REQUEST_PARAMETER_NOTE_TO_PAYER = 'noteToPayer';
    public const REQUEST_PARAMETER_PARTNER_ATTRIBUTION_ID = 'partnerAttributionId';
    public const REQUEST_PARAMETER_IS_FINAL = 'isFinal';

    /**
     * @internal
     */
    public function __construct(
        private readonly OrderResource $orderResource,
        private readonly AuthorizationResource $authorizationResource,
        private readonly CaptureResource $captureResource,
        private readonly RefundResource $refundResource,
        private readonly EntityRepository $orderTransactionRepository,
        private readonly PaymentStatusUtilV2 $paymentStatusUtil,
        private readonly CaptureRefundCreator $captureRefundCreator,
    ) {
    }

    #[OA\Get(
        path: '/api/paypal-v2/order/{orderTransactionId}/{paypalOrderId}',
        operationId: 'orderDetails',
        description: 'Loads the order details of the given PayPal order ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'paypalOrderId',
                name: 'paypalOrderId',
                description: 'ID of the PayPal order',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal order',
            content: new OA\JsonContent(ref: Order::class)
        )]
    )]
    #[Route(path: '/api/paypal-v2/order/{orderTransactionId}/{paypalOrderId}', name: 'api.paypal_v2.order_details', defaults: ['_acl' => ['order.viewer']], methods: ['GET'])]
    public function orderDetails(string $orderTransactionId, string $paypalOrderId, Context $context): JsonResponse
    {
        try {
            $paypalOrder = $this->orderResource->get(
                $paypalOrderId,
                $this->getSalesChannelId($orderTransactionId, $context)
            );
        } catch (PayPalApiException $e) {
            if ($e->getStatusCode() === Response::HTTP_NOT_FOUND) {
                throw new OrderNotFoundException($paypalOrderId);
            }

            throw $e;
        }

        return new JsonResponse($paypalOrder);
    }

    #[OA\Get(
        path: '/api/paypal-v2/authorization/{orderTransactionId}/{authorizationId}',
        operationId: 'authorizationDetails',
        description: 'Loads the authorization details of the given PayPal authorization ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'authorizationId',
                name: 'authorizationId',
                description: 'ID of the PayPal authorization',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal authorization',
            content: new OA\JsonContent(ref: Order\PurchaseUnit\Payments\Authorization::class)
        )]
    )]
    #[Route(path: '/api/paypal-v2/authorization/{orderTransactionId}/{authorizationId}', name: 'api.paypal_v2.authorization_details', defaults: ['_acl' => ['order.viewer']], methods: ['GET'])]
    public function authorizationDetails(string $orderTransactionId, string $authorizationId, Context $context): JsonResponse
    {
        $authorization = $this->authorizationResource->get(
            $authorizationId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($authorization);
    }

    #[OA\Get(
        path: '/api/paypal-v2/capture/{orderTransactionId}/{captureId}',
        operationId: 'captureDetails',
        description: 'Loads the capture details of the given PayPal capture ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'captureId',
                name: 'captureId',
                description: 'ID of the PayPal capture',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal capture',
            content: new OA\JsonContent(ref: Order\PurchaseUnit\Payments\Capture::class)
        )]
    )]
    #[Route(path: '/api/paypal-v2/capture/{orderTransactionId}/{captureId}', name: 'api.paypal_v2.capture_details', defaults: ['_acl' => ['order.viewer']], methods: ['GET'])]
    public function captureDetails(string $orderTransactionId, string $captureId, Context $context): JsonResponse
    {
        $capture = $this->captureResource->get(
            $captureId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($capture);
    }

    #[OA\Get(
        path: '/api/paypal-v2/refund/{orderTransactionId}/{refundId}',
        operationId: 'refundDetails',
        description: 'Loads the refund details of the given PayPal refund ID',
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'refundId',
                name: 'refundId',
                description: 'ID of the PayPal refund',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal refund',
            content: new OA\JsonContent(ref: Order\PurchaseUnit\Payments\Refund::class)
        )]
    )]
    #[Route(path: '/api/paypal-v2/refund/{orderTransactionId}/{refundId}', name: 'api.paypal_v2.refund_details', defaults: ['_acl' => ['order.viewer']], methods: ['GET'])]
    public function refundDetails(string $orderTransactionId, string $refundId, Context $context): JsonResponse
    {
        $refund = $this->refundResource->get(
            $refundId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($refund);
    }

    #[OA\Post(
        path: '/api/_action/paypal-v2/refund-capture/{orderTransactionId}/{captureId}/{paypalOrderId}',
        operationId: 'refundCapture',
        description: 'Refunds the PayPal capture and sets the state of the Shopware order transaction accordingly',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'partnerAttributionId', description: "Partner Attribution ID. See Swag\PayPal\RestApi\PartnerAttributionId", type: 'string'),
            new OA\Property(property: 'amount', description: 'Amount which should be refunded', type: 'string'),
            new OA\Property(property: 'currency', description: 'Currency of the refund', type: 'string'),
            new OA\Property(property: 'invoiceNumber', description: 'Invoice number of the refund', type: 'string'),
            new OA\Property(property: 'noteToPayer', description: 'A note to the payer sent with the refund', type: 'string'),
        ])),
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'captureId',
                name: 'captureId',
                description: 'ID of the PayPal capture',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                parameter: 'paypalOrderId',
                name: 'paypalOrderId',
                description: 'ID of the PayPal order',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal refund',
            content: new OA\JsonContent(ref: Order\PurchaseUnit\Payments\Refund::class)
        )]
    )]
    #[Route(path: '/api/_action/paypal-v2/refund-capture/{orderTransactionId}/{captureId}/{paypalOrderId}', name: 'api.action.paypal_v2.refund_capture', defaults: ['_acl' => ['order.editor']], methods: ['POST'])]
    public function refundCapture(
        string $orderTransactionId,
        string $captureId,
        string $paypalOrderId,
        Context $context,
        Request $request,
    ): JsonResponse {
        $refund = $this->captureRefundCreator->createRefund($request);
        $salesChannelId = $this->getSalesChannelId($orderTransactionId, $context);

        $refundResponse = $this->captureResource->refund(
            $captureId,
            $refund,
            $salesChannelId,
            $this->getPartnerAttributionId($request),
            false
        );

        $order = $this->orderResource->get($paypalOrderId, $salesChannelId);

        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $refundResponse, $order, $context);

        return new JsonResponse($refundResponse);
    }

    #[OA\Post(
        path: '/api/_action/paypal-v2/capture-authorization/{orderTransactionId}/{authorizationId}',
        operationId: 'captureAuthorization',
        description: 'Captures the PayPal authorization and sets the state of the Shopware order transaction accordingly',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'partnerAttributionId', description: "Partner Attribution ID. See Swag\PayPal\RestApi\PartnerAttributionId", type: 'string'),
            new OA\Property(property: 'amount', description: 'Amount which should be captured', type: 'string'),
            new OA\Property(property: 'currency', description: 'Currency of the capture', type: 'string'),
            new OA\Property(property: 'invoiceNumber', description: 'Invoice number of the capture', type: 'string'),
            new OA\Property(property: 'noteToPayer', description: 'A note to the payer sent with the capture', type: 'string'),
            new OA\Property(property: 'isFinal', description: 'Define if this is the final capture', type: 'boolean'),
        ])),
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'authorizationId',
                name: 'authorizationId',
                description: 'ID of the PayPal authorization',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_OK,
            description: 'Details of the PayPal capture',
            content: new OA\JsonContent(ref: Order\PurchaseUnit\Payments\Capture::class)
        )]
    )]
    #[Route(path: '/api/_action/paypal-v2/capture-authorization/{orderTransactionId}/{authorizationId}', name: 'api.action.paypal_v2.capture_authorization', defaults: ['_acl' => ['order.editor']], methods: ['POST'])]
    public function captureAuthorization(
        string $orderTransactionId,
        string $authorizationId,
        Context $context,
        Request $request,
    ): JsonResponse {
        $capture = $this->captureRefundCreator->createCapture($request);

        $captureResponse = $this->authorizationResource->capture(
            $authorizationId,
            $capture,
            $this->getSalesChannelId($orderTransactionId, $context),
            $this->getPartnerAttributionId($request),
            false
        );

        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $captureResponse, $context);

        return new JsonResponse($captureResponse);
    }

    #[OA\Post(
        path: '/api/_action/paypal-v2/void-authorization/{orderTransactionId}/{authorizationId}',
        operationId: 'voidAuthorization',
        description: 'Voids the PayPal authorization and sets the state of the Shopware order transaction accordingly',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'partnerAttributionId', description: "Partner Attribution ID. See Swag\PayPal\RestApi\PartnerAttributionId", type: 'string'),
        ])),
        tags: ['Admin API', 'PayPal'],
        parameters: [
            new OA\Parameter(
                parameter: 'orderTransactionId',
                name: 'orderTransactionId',
                description: 'ID of the order transaction which contains the PayPal payment',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9a-f]{32}$')
            ),
            new OA\Parameter(
                parameter: 'authorizationId',
                name: 'authorizationId',
                description: 'ID of the PayPal authorization',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [new OA\Response(
            response: Response::HTTP_NO_CONTENT,
            description: 'Avoidance was successful',
        )]
    )]
    #[Route(path: '/api/_action/paypal-v2/void-authorization/{orderTransactionId}/{authorizationId}', name: 'api.action.paypal_v2.void_authorization', defaults: ['_acl' => ['order.editor']], methods: ['POST'])]
    public function voidAuthorization(
        string $orderTransactionId,
        string $authorizationId,
        Context $context,
        Request $request,
    ): Response {
        $this->authorizationResource->void(
            $authorizationId,
            $this->getSalesChannelId($orderTransactionId, $context),
            $this->getPartnerAttributionId($request)
        );

        $this->paymentStatusUtil->applyVoidState($orderTransactionId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function getSalesChannelId(string $orderTransactionId, Context $context): string
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($orderTransaction === null) {
            throw PaymentException::invalidTransaction($orderTransactionId);
        }

        $order = $orderTransaction->getOrder();

        if ($order === null) {
            throw new OrderNotFoundException($orderTransactionId);
        }

        return $order->getSalesChannelId();
    }

    private function getPartnerAttributionId(Request $request): string
    {
        return (string) $request->request->get(
            self::REQUEST_PARAMETER_PARTNER_ATTRIBUTION_ID,
            PartnerAttributionId::PAYPAL_CLASSIC
        );
    }
}
