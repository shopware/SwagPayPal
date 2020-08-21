<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Resource;

use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\PayPal\ApiV2\RequestUriV2;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;

class RefundResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function get(string $refundId, string $salesChannelId): Refund
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV2::REFUNDS_RESOURCE, $refundId)
        );

        return (new Refund())->assign($response);
    }
}
