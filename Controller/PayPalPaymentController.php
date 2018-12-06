<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Controller;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Exception\RequiredParameterMissingException;
use SwagPayPal\PayPal\Resource\PaymentResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class PayPalPaymentController extends AbstractController
{
    public const REQUEST_PARAMETER_PAYMENT_ID = 'paymentId';

    /**
     * @var PaymentResource
     */
    private $paymentResource;

    public function __construct(PaymentResource $paymentResource)
    {
        $this->paymentResource = $paymentResource;
    }

    /**
     * @Route("/api/v{version}/paypal/payment-details", name="api.paypal.payment.details", methods={"GET"})
     *
     * @throws RequiredParameterMissingException
     */
    public function getPaymentDetails(Request $request, Context $context): JsonResponse
    {
        $paymentId = (string) $request->query->get(self::REQUEST_PARAMETER_PAYMENT_ID, '');

        if ($paymentId === '') {
            throw new RequiredParameterMissingException(self::REQUEST_PARAMETER_PAYMENT_ID);
        }

        $payment = $this->paymentResource->get($paymentId, $context);

        return new JsonResponse($payment);
    }
}
