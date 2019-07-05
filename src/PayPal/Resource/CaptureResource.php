<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class CaptureResource
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
    public function refund(string $captureId, Refund $refund, string $salesChannelId): Refund
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            RequestUri::CAPTURE_RESOURCE . '/' . $captureId . '/refund',
            $refund
        );

        $refund->assign($response);

        return $refund;
    }
}
