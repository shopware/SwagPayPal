<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Controller;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Refund;
use SwagPayPal\PayPal\Api\Refund\Amount;
use SwagPayPal\PayPal\Exception\RequiredParameterInvalidException;
use SwagPayPal\PayPal\PaymentIntent;
use SwagPayPal\PayPal\Resource\PaymentResource;
use SwagPayPal\PayPal\Resource\SaleResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PayPalPaymentController extends AbstractController
{
    public const REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';

    public const REQUEST_PARAMETER_INTENT = 'intent';

    public const REQUEST_PARAMETER_REFUND_AMOUNT = 'refundAmount';

    public const REQUEST_PARAMETER_REFUND_CURRENCY = 'refundCurrency';

    public const REQUEST_PARAMETER_REFUND_INVOICE_NUMBER = 'refundInvoiceNumber';

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    /**
     * @var SaleResource
     */
    private $saleResource;

    public function __construct(PaymentResource $paymentResource, SaleResource $saleResource)
    {
        $this->paymentResource = $paymentResource;
        $this->saleResource = $saleResource;
    }

    /**
     * @Route("/api/v{version}/paypal/payment-details/{paymentId}", name="api.paypal.payment.details", methods={"GET"})
     */
    public function paymentDetails(Context $context, string $paymentId): JsonResponse
    {
        $payment = $this->paymentResource->get($paymentId, $context);

        return new JsonResponse($payment);
    }

    /**
     * @Route("/api/v{version}/_action/paypal/refund-payment/{intent}/{paymentId}", name="api.action.paypal.refund.payment", methods={"POST"})
     *
     * @throws RequiredParameterInvalidException
     */
    public function refundPayment(Request $request, Context $context, string $intent, string $paymentId): JsonResponse
    {
        $refundAmount = (string) round((float) $request->request->get(self::REQUEST_PARAMETER_REFUND_AMOUNT), 2);
        $currency = $request->request->getAlpha(self::REQUEST_PARAMETER_REFUND_CURRENCY);
        $invoiceNumber = (string) $request->request->get(self::REQUEST_PARAMETER_REFUND_INVOICE_NUMBER, '');

        $refund = new Refund();
        if ($invoiceNumber !== '') {
            $refund->setInvoiceNumber($invoiceNumber);
        }

        if ($refundAmount !== '0') {
            $amount = new Amount();
            $amount->setTotal($refundAmount);
            $amount->setCurrency($currency);

            $refund->setAmount($amount);
        }

        switch ($intent) {
            case PaymentIntent::SALE:
                $refundResponse = $this->saleResource->refund($paymentId, $refund, $context);
                break;
            case PaymentIntent::AUTHORIZE:
            case PaymentIntent::ORDER:
                $refundResponse = new Refund(); // TODO PT-10003 capture refund
                break;
            default:
                throw new RequiredParameterInvalidException(self::REQUEST_PARAMETER_INTENT);
        }

        return new JsonResponse($refundResponse);
    }
}
