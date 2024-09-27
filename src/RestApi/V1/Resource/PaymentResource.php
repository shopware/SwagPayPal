<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\ExecutePayerInfo;
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class PaymentResource
{
    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function create(Payment $payment, string $salesChannelId, string $partnerAttributionId): Payment
    {
        $paypalClient = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId);
        $response = $paypalClient->sendPostRequest(RequestUriV1::PAYMENT_RESOURCE, $payment);

        $payment->assign($response);

        return $payment;
    }

    public function execute(
        string $payerId,
        string $paymentId,
        string $salesChannelId,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
    ): Payment {
        $payerInfo = new ExecutePayerInfo();
        $payerInfo->setPayerId($payerId);
        $paypalClient = $this->payPalClientFactory->getPayPalClient($salesChannelId, $partnerAttributionId);
        $response = $paypalClient->sendPostRequest(
            \sprintf('%s/%s/execute', RequestUriV1::PAYMENT_RESOURCE, $paymentId),
            $payerInfo
        );

        return (new Payment())->assign($response);
    }

    public function get(string $paymentId, string $salesChannelId): Payment
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV1::PAYMENT_RESOURCE, $paymentId)
        );

        return (new Payment())->assign($response);
    }

    /**
     * @param Patch[] $patches
     */
    public function patch(array $patches, string $paymentId, string $salesChannelId): Payment
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPatchRequest(
            \sprintf('%s/%s', RequestUriV1::PAYMENT_RESOURCE, $paymentId),
            $patches
        );

        return (new Payment())->assign($response);
    }
}
