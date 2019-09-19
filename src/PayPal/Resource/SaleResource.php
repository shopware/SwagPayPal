<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class SaleResource
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
    public function refund(string $saleId, Refund $refund, string $salesChannelId): Refund
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            RequestUri::SALE_RESOURCE . '/' . $saleId . '/refund',
            $refund
        );

        $refund->assign($response);

        return $refund;
    }
}
