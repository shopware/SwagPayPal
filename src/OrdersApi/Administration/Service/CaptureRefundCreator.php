<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\OrdersApi\Administration\Service;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\OrdersApi\Administration\Exception\RequestParameterInvalidException;
use Swag\PayPal\OrdersApi\Administration\PayPalOrdersController;
use Swag\PayPal\RestApi\V2\Api\Common\Money;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\Request;

#[Package('checkout')]
class CaptureRefundCreator
{
    private PriceFormatter $priceFormatter;

    /**
     * @internal
     */
    public function __construct(PriceFormatter $priceFormatter)
    {
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * @throws RequestParameterInvalidException
     */
    public function createRefund(Request $request): Refund
    {
        $refund = new Refund();
        $amount = $this->getAmount($request);
        $refund->setAmount($amount);
        $this->setInvoiceId($refund, $request);
        $this->setNoteToPayer($refund, $request);

        return $refund;
    }

    /**
     * @throws RequestParameterInvalidException
     */
    public function createCapture(Request $request): Capture
    {
        $isFinalCapture = $request->request->getBoolean(PayPalOrdersController::REQUEST_PARAMETER_IS_FINAL, true);

        $capture = new Capture();
        $amount = $this->getAmount($request);
        $capture->setAmount($amount);
        $this->setInvoiceId($capture, $request);
        $this->setNoteToPayer($capture, $request);
        $capture->setFinalCapture($isFinalCapture);

        return $capture;
    }

    private function getAmount(Request $request): ?Money
    {
        $amount = new Money();
        $amount->setCurrencyCode($request->request->getAlpha(PayPalOrdersController::REQUEST_PARAMETER_CURRENCY));

        $amountString = $this->priceFormatter->formatPrice(
            (float) $request->request->get(PayPalOrdersController::REQUEST_PARAMETER_AMOUNT),
            $amount->getCurrencyCode(),
        );
        if ($amountString === '0.00') {
            return null;
        }

        $amount->setValue($amountString);

        return $amount;
    }

    private function setInvoiceId(Refund|Capture $refundCapture, Request $request): void
    {
        $invoiceId = (string) $request->request->get(PayPalOrdersController::REQUEST_PARAMETER_INVOICE_NUMBER, '');
        if ($invoiceId === '') {
            return;
        }

        try {
            $refundCapture->setInvoiceId($invoiceId);
        } catch (\LengthException $e) {
            throw new RequestParameterInvalidException(
                PayPalOrdersController::REQUEST_PARAMETER_INVOICE_NUMBER,
                $e->getMessage()
            );
        }
    }

    private function setNoteToPayer(Refund|Capture $refundCapture, Request $request): void
    {
        $noteToPayer = (string) $request->request->get(PayPalOrdersController::REQUEST_PARAMETER_NOTE_TO_PAYER, '');
        if ($noteToPayer === '') {
            return;
        }

        try {
            $refundCapture->setNoteToPayer($noteToPayer);
        } catch (\LengthException $e) {
            throw new RequestParameterInvalidException(
                PayPalOrdersController::REQUEST_PARAMETER_NOTE_TO_PAYER,
                $e->getMessage()
            );
        }
    }
}
