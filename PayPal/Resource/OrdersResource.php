<?php declare(strict_types=1);

namespace SwagPayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Capture;
use SwagPayPal\PayPal\Api\DoVoid;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\RequestUri;

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

    public function capture(string $orderId, Capture $capture, Context $context): Capture
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::ORDERS_RESOURCE . '/' . $orderId . '/capture',
            $capture
        );

        $capture->assign($response);

        return $capture;
    }

    public function void(string $orderId, Context $context): DoVoid
    {
        $doVoid = new DoVoid();
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::ORDERS_RESOURCE . '/' . $orderId . '/do-void',
            $doVoid
        );

        $doVoid->assign($response);

        return $doVoid;
    }
}
