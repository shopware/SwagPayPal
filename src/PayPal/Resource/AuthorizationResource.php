<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Resource;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Capture;
use Swag\PayPal\PayPal\Api\DoVoid;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;

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

        $capture->assign($response);

        return $capture;
    }

    public function void(string $authorizationId, Context $context): DoVoid
    {
        $doVoid = new DoVoid();
        $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
            RequestUri::AUTHORIZATION_RESOURCE . '/' . $authorizationId . '/void',
            $doVoid
        );

        $doVoid->assign($response);

        return $doVoid;
    }
}
