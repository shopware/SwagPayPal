<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Administration;

use OpenApi\Annotations as OA;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture\Amount as CaptureAmount;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Refund\Amount as RefundAmount;
use Swag\PayPal\PayPal\ApiV2\Resource\AuthorizationResource;
use Swag\PayPal\PayPal\ApiV2\Resource\CaptureResource;
use Swag\PayPal\PayPal\ApiV2\Resource\OrderResource;
use Swag\PayPal\PayPal\ApiV2\Resource\RefundResource;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class PayPalOrdersController extends AbstractController
{
    public const REQUEST_PARAMETER_CURRENCY = 'currency';
    public const REQUEST_PARAMETER_AMOUNT = 'amount';
    public const REQUEST_PARAMETER_INVOICE_NUMBER = 'invoiceNumber';
    public const REQUEST_PARAMETER_NOTE_TO_PAYER = 'noteToPayer';

    /**
     * @var OrderResource
     */
    private $orderResource;

    /**
     * @var AuthorizationResource
     */
    private $authorizationResource;

    /**
     * @var CaptureResource
     */
    private $captureResource;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepository;

    /**
     * @var RefundResource
     */
    private $refundResource;

    /**
     * @var PaymentStatusUtilV2
     */
    private $paymentStatusUtil;

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    public function __construct(
        OrderResource $orderResource,
        AuthorizationResource $authorizationResource,
        CaptureResource $captureResource,
        RefundResource $refundResource,
        EntityRepositoryInterface $orderTransactionRepository,
        PaymentStatusUtilV2 $paymentStatusUtil,
        PriceFormatter $priceFormatter
    ) {
        $this->orderResource = $orderResource;
        $this->authorizationResource = $authorizationResource;
        $this->captureResource = $captureResource;
        $this->refundResource = $refundResource;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->paymentStatusUtil = $paymentStatusUtil;
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * @OA\Get(
     *     path="/paypal-v2/order/{orderTransactionId}/{paypalOrderId}",
     *     description="Loads the order details of the given PayPal order ID",
     *     operationId="orderDetails",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="paypalOrderId",
     *         name="paypalOrderId",
     *         in="path",
     *         description="ID of the PayPal order",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal order",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/paypal-v2/order/{orderTransactionId}/{paypalOrderId}",
     *      name="api.paypal_v2.order_details",
     *      methods={"GET"}
     * )
     */
    public function orderDetails(string $orderTransactionId, string $paypalOrderId, Context $context): JsonResponse
    {
        $payment = $this->orderResource->get(
            $paypalOrderId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($payment);
    }

    /**
     * @OA\Get(
     *     path="/paypal-v2/authorization/{orderTransactionId}/{authorizationId}",
     *     description="Loads the authorization details of the given PayPal authorization ID",
     *     operationId="authorizationDetails",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="authorizationId",
     *         name="authorizationId",
     *         in="path",
     *         description="ID of the PayPal authorization",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal authorization",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/paypal-v2/authorization/{orderTransactionId}/{authorizationId}",
     *      name="api.paypal_v2.authorization_details",
     *      methods={"GET"}
     * )
     */
    public function authorizationDetails(string $orderTransactionId, string $authorizationId, Context $context): JsonResponse
    {
        $authorization = $this->authorizationResource->get(
            $authorizationId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($authorization);
    }

    /**
     * @OA\Get(
     *     path="/paypal-v2/capture/{orderTransactionId}/{captureId}",
     *     description="Loads the capture details of the given PayPal capture ID",
     *     operationId="captureDetails",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="captureId",
     *         name="captureId",
     *         in="path",
     *         description="ID of the PayPal capture",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal capture",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/paypal-v2/capture/{orderTransactionId}/{captureId}",
     *      name="api.paypal_v2.capture_details",
     *      methods={"GET"}
     * )
     */
    public function captureDetails(string $orderTransactionId, string $captureId, Context $context): JsonResponse
    {
        $capture = $this->captureResource->get(
            $captureId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($capture);
    }

    /**
     * @OA\Get(
     *     path="/paypal-v2/refund/{orderTransactionId}/{refundId}",
     *     description="Loads the refund details of the given PayPal refund ID",
     *     operationId="refundDetails",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="refundId",
     *         name="refundId",
     *         in="path",
     *         description="ID of the PayPal refund",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal refund",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/paypal-v2/refund/{orderTransactionId}/{refundId}",
     *      name="api.paypal_v2.refund_details",
     *      methods={"GET"}
     * )
     */
    public function refundDetails(string $orderTransactionId, string $refundId, Context $context): JsonResponse
    {
        $refund = $this->refundResource->get(
            $refundId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        return new JsonResponse($refund);
    }

    /**
     * @OA\Post(
     *     path="/_action/paypal-v2/refund-capture/{orderTransactionId}/{captureId}/{paypalOrderId}",
     *     description="Refunds the PayPal capture and sets the state of the Shopware order transaction accordingly",
     *     operationId="refundCapture",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="captureId",
     *         name="captureId",
     *         in="path",
     *         description="ID of the PayPal capture",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="paypalOrderId",
     *         name="paypalOrderId",
     *         in="path",
     *         description="ID of the PayPal order",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal refund",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/_action/paypal-v2/refund-capture/{orderTransactionId}/{captureId}/{paypalOrderId}",
     *     name="api.action.paypal_v2.refund_capture",
     *     methods={"POST"}
     * )
     */
    public function refundCapture(
        string $orderTransactionId,
        string $captureId,
        string $paypalOrderId,
        Context $context,
        Request $request
    ): JsonResponse {
        $refund = $this->createRefund($request);
        $salesChannelId = $this->getSalesChannelId($orderTransactionId, $context);

        $refundResponse = $this->captureResource->refund(
            $captureId,
            $refund,
            $salesChannelId,
            false
        );

        $order = $this->orderResource->get($paypalOrderId, $salesChannelId);

        $this->paymentStatusUtil->applyRefundState($orderTransactionId, $refundResponse, $order, $context);

        return new JsonResponse($refundResponse);
    }

    /**
     * @OA\Post(
     *     path="/_action/paypal-v2/capture-authorization/{orderTransactionId}/{authorizationId}",
     *     description="Captures the PayPal authorization and sets the state of the Shopware order transaction accordingly",
     *     operationId="captureAuthorization",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="authorizationId",
     *         name="authorizationId",
     *         in="path",
     *         description="ID of the PayPal authorization",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Details of the PayPal capture",
     *         @OA\JsonContent(type="array")
     *     )
     * )
     * @Route(
     *     "/api/v{version}/_action/paypal-v2/capture-authorization/{orderTransactionId}/{authorizationId}",
     *     name="api.action.paypal_v2.capture_authorization",
     *     methods={"POST"}
     * )
     */
    public function captureAuthorization(
        string $orderTransactionId,
        string $authorizationId,
        Context $context,
        Request $request
    ): JsonResponse {
        $capture = $this->createCapture($request);

        $captureResponse = $this->authorizationResource->capture(
            $authorizationId,
            $capture,
            $this->getSalesChannelId($orderTransactionId, $context),
            false
        );

        $this->paymentStatusUtil->applyCaptureState($orderTransactionId, $captureResponse, $context);

        return new JsonResponse($captureResponse);
    }

    /**
     * @OA\Post(
     *     path="/_action/paypal-v2/void-authorization/{orderTransactionId}/{authorizationId}",
     *     description="Voids the PayPal authorization and sets the state of the Shopware order transaction accordingly",
     *     operationId="voidAuthorization",
     *     tags={"Admin API", "PayPal"},
     *     @OA\Parameter(
     *         parameter="orderTransactionId",
     *         name="orderTransactionId",
     *         in="path",
     *         description="ID of the order transaction which contains the PayPal payment",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         parameter="authorizationId",
     *         name="authorizationId",
     *         in="path",
     *         description="ID of the PayPal authorization",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="204",
     *         description="Returns status 204 if the voidance was succesful",
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Returns status 400 if the voidance was not succesful",
     *     )
     * )
     * @Route(
     *     "/api/v{version}/_action/paypal-v2/void-authorization/{orderTransactionId}/{authorizationId}",
     *     name="api.action.paypal_v2.void_authorization",
     *     methods={"POST"}
     * )
     */
    public function voidAuthorization(
        string $orderTransactionId,
        string $authorizationId,
        Context $context
    ): Response {
        $voidResponse = $this->authorizationResource->void(
            $authorizationId,
            $this->getSalesChannelId($orderTransactionId, $context)
        );

        if ($voidResponse === false) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $this->paymentStatusUtil->applyVoidState($orderTransactionId, $context);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    /**
     * @throws OrderNotFoundException
     */
    private function getSalesChannelId(string $orderTransactionId, Context $context): string
    {
        $criteria = new Criteria([$orderTransactionId]);
        $criteria->addAssociation('order');
        /** @var OrderTransactionEntity|null $orderTransaction */
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

        if ($orderTransaction === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        $order = $orderTransaction->getOrder();

        if ($order === null) {
            throw new InvalidTransactionException($orderTransactionId);
        }

        return $order->getSalesChannelId();
    }

    private function createRefund(Request $request): Refund
    {
        $refundAmount = $this->priceFormatter->formatPrice((float) $request->request->get(self::REQUEST_PARAMETER_AMOUNT));
        $currency = $request->request->getAlpha(self::REQUEST_PARAMETER_CURRENCY);
        $invoiceId = (string) $request->request->get(self::REQUEST_PARAMETER_INVOICE_NUMBER, '');
        $noteToPayer = (string) $request->request->get(self::REQUEST_PARAMETER_NOTE_TO_PAYER, '');

        $refund = new Refund();

        if ($refundAmount !== '0.00') {
            $amount = new RefundAmount();
            $amount->setValue($refundAmount);
            $amount->setCurrencyCode($currency);
            $refund->setAmount($amount);
        }

        if ($noteToPayer !== '') {
            $refund->setNoteToPayer($noteToPayer);
        }
        if ($invoiceId !== '') {
            $refund->setInvoiceId($invoiceId);
        }

        return $refund;
    }

    private function createCapture(Request $request): Capture
    {
        $captureAmount = $this->priceFormatter->formatPrice((float) $request->request->get(self::REQUEST_PARAMETER_AMOUNT));
        $currency = $request->request->getAlpha(self::REQUEST_PARAMETER_CURRENCY);
        $invoiceId = (string) $request->request->get(self::REQUEST_PARAMETER_INVOICE_NUMBER, '');
        $noteToPayer = (string) $request->request->get(self::REQUEST_PARAMETER_NOTE_TO_PAYER, '');

        $capture = new Capture();

        if ($captureAmount !== '0.00') {
            $amount = new CaptureAmount();
            $amount->setValue($captureAmount);
            $amount->setCurrencyCode($currency);
            $capture->setAmount($amount);
        }

        if ($noteToPayer !== '') {
            $capture->setNoteToPayer($noteToPayer);
        }
        if ($invoiceId !== '') {
            $capture->setInvoiceId($invoiceId);
        }

        return $capture;
    }
}
