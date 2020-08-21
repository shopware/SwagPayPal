<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV2\Resource;

use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\PayPal\ApiV2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\PayPal\ApiV2\RequestUriV2;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;

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

    public function get(string $authorizationId, string $salesChannelId): Authorization
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUriV2::AUTHORIZATIONS_RESOURCE, $authorizationId)
        );

        return (new Authorization())->assign($response);
    }

    public function capture(
        string $authorizationId,
        Capture $capture,
        string $salesChannelId,
        bool $minimalResponse = true
    ): Capture {
        $headers = [];
        if ($minimalResponse === false) {
            $headers['Prefer'] = 'return=representation';
        }

        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUriV2::AUTHORIZATIONS_RESOURCE, $authorizationId),
            $capture,
            $headers
        );

        return $capture->assign($response);
    }

    public function void(string $authorizationId, string $salesChannelId): bool
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/void', RequestUriV2::AUTHORIZATIONS_RESOURCE, $authorizationId),
            null
        );

        return $response === [];
    }
}
