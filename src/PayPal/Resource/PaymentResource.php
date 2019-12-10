<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Patch;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\Api\Payment\Payer\ExecutePayerInfo;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\RequestUri;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

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

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function create(Payment $payment, string $salesChannelId, string $partnerAttributionId): Payment
    {
        $paypalClient = $this->payPalClientFactory->createPaymentClient($salesChannelId, $partnerAttributionId);
        $response = $paypalClient->sendPostRequest(
            RequestUri::PAYMENT_RESOURCE,
            $payment
        );

        $payment->assign($response);

        return $payment;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function execute(
        string $payerId,
        string $paymentId,
        string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ): Payment {
        $payerInfo = new ExecutePayerInfo();
        $payerInfo->setPayerId($payerId);
        $paypalClient = $this->payPalClientFactory->createPaymentClient($salesChannelId, $partnerAttributionId);
        $response = $paypalClient->sendPostRequest(
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId . '/execute',
            $payerInfo
        );

        $paymentStruct = new Payment();
        $paymentStruct->assign($response);

        return $paymentStruct;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function get(string $paymentId, string $salesChannelId): Payment
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendGetRequest(
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId
        );

        $paymentStruct = new Payment();
        $paymentStruct->assign($response);

        return $paymentStruct;
    }

    /**
     * @param Patch[] $patches
     *
     * @throws PayPalSettingsInvalidException
     */
    public function patch(array $patches, string $paymentId, string $salesChannelId): Payment
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPatchRequest(
            RequestUri::PAYMENT_RESOURCE . '/' . $paymentId,
            $patches
        );

        $paymentStruct = new Payment();
        $paymentStruct->assign($response);

        return $paymentStruct;
    }
}
