<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Capture;
use Swag\PayPal\PayPal\Api\Refund;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;

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

    public function get(string $captureId, string $salesChannelId): Capture
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUri::CAPTURE_RESOURCE, $captureId)
        );

        return (new Capture())->assign($response);
    }

    public function refund(string $captureId, Refund $refund, string $salesChannelId): Refund
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/refund', RequestUri::CAPTURE_RESOURCE, $captureId),
            $refund
        );

        $refund->assign($response);

        return $refund;
    }
}
