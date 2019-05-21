<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\RequestUri;

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

    public function create(Payment $payment, Context $context, string $partnerAttributionId): Payment
    {
        $paypalClient = $this->payPalClientFactory->createPaymentClient($context, $partnerAttributionId);
        $response = $paypalClient->sendPostRequest(
            RequestUri::PAYMENT_RESOURCE,
            $payment
        );

        $payment->assign($response);

        return $payment;
    }

    public function execute(
        string $payerId,
        string $paymentId,
        Context $context,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): Payment {
        $payerInfo = new PayerInfo();
        $payerInfo->setPayerId($payerId);
        $paypalClient = $this->payPalClientFactory->createPaymentClient($context, $partnerAttributionId);
        $response = $paypalClient->sendPostRequest(
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
