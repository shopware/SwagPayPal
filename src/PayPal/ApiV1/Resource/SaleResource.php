<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Resource;

use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\RelatedResource\Sale;
use Swag\PayPal\PayPal\ApiV1\Api\Refund;
use Swag\PayPal\PayPal\ApiV1\RequestUriV1;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;

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

    public function get(string $saleId, string $salesChannelId): Sale
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV1::SALE_RESOURCE, $saleId)
        );

        return (new Sale())->assign($response);
    }

    public function refund(string $saleId, Refund $refund, string $salesChannelId): Refund
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/refund', RequestUriV1::SALE_RESOURCE, $saleId),
            $refund
        );

        $refund->assign($response);

        return $refund;
    }
}
