<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Controller;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Capture;
use SwagPayPal\PayPal\Api\Capture\Amount as CaptureAmount;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource;
use SwagPayPal\PayPal\Api\Refund;
use SwagPayPal\PayPal\Api\Refund\Amount as RefundAmount;
use SwagPayPal\PayPal\Exception\RequiredParameterInvalidException;
use SwagPayPal\PayPal\Resource\AuthorizationResource;
use SwagPayPal\PayPal\Resource\CaptureResource;
use SwagPayPal\PayPal\Resource\OrdersResource;
use SwagPayPal\PayPal\Resource\PaymentResource;
use SwagPayPal\PayPal\Resource\SaleResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PayPalPaymentController extends AbstractController
{
    public const REQUEST_PARAMETER_CURRENCY = 'currency';

    public const REQUEST_PARAMETER_REFUND_AMOUNT = 'refundAmount';
    public const REQUEST_PARAMETER_REFUND_INVOICE_NUMBER = 'refundInvoiceNumber';

    public const REQUEST_PARAMETER_CAPTURE_AMOUNT = 'captureAmount';
    public const REQUEST_PARAMETER_CAPTURE_IS_FINAL = 'captureIsFinal';

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var SaleResource
     */
    private $saleResource;

    /**
     * @var AuthorizationResource
     */
    private $authorizationResource;

    /**
     * @var OrdersResource
     */
    private $ordersResource;

    /**
     * @var CaptureResource
     */
    private $captureResource;

    public function __construct(
        PaymentResource $paymentResource,
        SaleResource $saleResource,
        AuthorizationResource $authorizationResource,
        OrdersResource $ordersResource,
        CaptureResource $captureResource
    ) {
        $this->paymentResource = $paymentResource;
        $this->saleResource = $saleResource;
        $this->authorizationResource = $authorizationResource;
        $this->ordersResource = $ordersResource;
        $this->captureResource = $captureResource;
    }

    /**
     * @Route("/api/v{version}/paypal/payment-details/{paymentId}", name="api.paypal.payment_details", methods={"GET"})
     */
    public function paymentDetails(Context $context, string $paymentId): JsonResponse
    {
        $payment = $this->paymentResource->get($paymentId, $context);

        return new JsonResponse($payment);
    }

    /**
     * @Route("/api/v{version}/_action/paypal/refund-payment/{resourceType}/{resourceId}", name="api.action.paypal.refund_payment", methods={"POST"})
     *
     * @throws RequiredParameterInvalidException
     */
    public function refundPayment(Request $request, Context $context, string $resourceType, string $resourceId): JsonResponse
    {
        $refund = $this->createRefund($request);

        switch ($resourceType) {
            case RelatedResource::SALE:
                $refundResponse = $this->saleResource->refund($resourceId, $refund, $context);
                break;
            case RelatedResource::CAPTURE:
                $refundResponse = $this->captureResource->refund($resourceId, $refund, $context);
                break;
            default:
                throw new RequiredParameterInvalidException('resourceType');
        }

        return new JsonResponse($refundResponse);
    }

    /**
     * @Route("/api/v{version}/_action/paypal/capture-payment/{resourceType}/{resourceId}", name="api.action.paypal.catpure_payment", methods={"POST"})
     *
     * @throws RequiredParameterInvalidException
     */
    public function capturePayment(Request $request, Context $context, string $resourceType, string $resourceId): JsonResponse
    {
        $capture = $this->createCapture($request);

        switch ($resourceType) {
            case RelatedResource::AUTHORIZE:
                $captureResponse = $this->authorizationResource->capture($resourceId, $capture, $context);
                break;
            case RelatedResource::ORDER:
                $captureResponse = $this->ordersResource->capture($resourceId, $capture, $context);
                break;
            default:
                throw new RequiredParameterInvalidException('resourceType');
        }

        return new JsonResponse($captureResponse);
    }

    /**
     * @Route("/api/v{version}/_action/paypal/void-payment/{resourceType}/{resourceId}", name="api.action.paypal.void_payment", methods={"POST"})
     *
     * @throws RequiredParameterInvalidException
     */
    public function voidPayment(Context $context, string $resourceType, string $resourceId): JsonResponse
    {
        switch ($resourceType) {
            case RelatedResource::AUTHORIZE:
                $voidResponse = $this->authorizationResource->void($resourceId, $context);
                break;
            case RelatedResource::ORDER:
                $voidResponse = $this->ordersResource->void($resourceId, $context);
                break;
            default:
                throw new RequiredParameterInvalidException('resourceType');
        }

        return new JsonResponse($voidResponse);
    }

    private function createRefund(Request $request): Refund
    {
        $refundAmount = (string) round((float) $request->request->get(self::REQUEST_PARAMETER_REFUND_AMOUNT), 2);
        $currency = $request->request->getAlpha(self::REQUEST_PARAMETER_CURRENCY);
        $invoiceNumber = (string) $request->request->get(self::REQUEST_PARAMETER_REFUND_INVOICE_NUMBER, '');

        $refund = new Refund();
        if ($invoiceNumber !== '') {
            $refund->setInvoiceNumber($invoiceNumber);
        }

        if ($refundAmount !== '0') {
            $amount = new RefundAmount();
            $amount->setTotal($refundAmount);
            $amount->setCurrency($currency);

            $refund->setAmount($amount);
        }

        return $refund;
    }

    private function createCapture(Request $request): Capture
    {
        $amountToCapture = (string) round((float) $request->request->get(self::REQUEST_PARAMETER_CAPTURE_AMOUNT), 2);
        $currency = $request->request->getAlpha(self::REQUEST_PARAMETER_CURRENCY);
        $isFinalCapture = $request->request->getBoolean(self::REQUEST_PARAMETER_CAPTURE_IS_FINAL, true);

        $capture = new Capture();
        $capture->setIsFinalCapture($isFinalCapture);
        $amount = new CaptureAmount();
        $amount->setTotal($amountToCapture);
        $amount->setCurrency($currency);

        $capture->setAmount($amount);

        return $capture;
    }
}
