<?php declare(strict_types=1);

namespace SwagPayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Capture;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\RequestUri;

class AuthorizationResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function capture(string $authorizationId, Capture $capture, Context $context): Capture
    {
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::AUTHORIZATION_RESOURCE . '/' . $authorizationId . '/capture',
            $capture
        );

        $refundStruct = new Capture();
        $refundStruct->assign($response);

        return $refundStruct;
    }
}
