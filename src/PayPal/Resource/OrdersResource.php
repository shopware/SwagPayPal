<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Capture;
use Swag\PayPal\PayPal\Api\DoVoid;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;

class OrdersResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function capture(string $orderId, Capture $capture, string $salesChannelId): Capture
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            RequestUri::ORDERS_RESOURCE . '/' . $orderId . '/capture',
            $capture
        );

        $capture->assign($response);

        return $capture;
    }

    public function void(string $orderId, string $salesChannelId): DoVoid
    {
        $doVoid = new DoVoid();
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            RequestUri::ORDERS_RESOURCE . '/' . $orderId . '/do-void',
            $doVoid
        );

        $doVoid->assign($response);

        return $doVoid;
    }
}
