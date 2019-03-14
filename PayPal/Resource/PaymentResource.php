<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\Api\Payment\Payer\PayerInfo;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\RequestUri;

class PaymentResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function create(Payment $payment, Context $context): Payment
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::PAYMENT_RESOURCE,
            $payment
        );

        $payment->assign($response);

        return $payment;
    }

    public function execute(string $payerId, string $paymentId, Context $context): Payment
    {
        $payerInfo = new PayerInfo();
        $payerInfo->setPayerId($payerId);
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId . '/execute',
            $payerInfo
        );

        $paymentStruct = new Payment();
        $paymentStruct->assign($response);

        return $paymentStruct;
    }

    public function get(string $paymentId, Context $context): Payment
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendGetRequest(
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId
        );

        $paymentStruct = new Payment();
        $paymentStruct->assign($response);

        return $paymentStruct;
    }
}
