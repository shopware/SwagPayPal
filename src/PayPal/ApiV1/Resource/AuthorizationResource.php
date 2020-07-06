<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\ApiV1\Resource;

use Swag\PayPal\PayPal\ApiV1\Api\Capture;
use Swag\PayPal\PayPal\ApiV1\Api\DoVoid;
use Swag\PayPal\PayPal\ApiV1\Api\Payment\Transaction\RelatedResource\Authorization;
use Swag\PayPal\PayPal\ApiV1\RequestUriV1;
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
            \sprintf('%s/%s', RequestUriV1::AUTHORIZATION_RESOURCE, $authorizationId)
        );

        return (new Authorization())->assign($response);
    }

    public function capture(string $authorizationId, Capture $capture, string $salesChannelId): Capture
    {
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUriV1::AUTHORIZATION_RESOURCE, $authorizationId),
            $capture
        );

        $capture->assign($response);

        return $capture;
    }

    public function void(string $authorizationId, string $salesChannelId): DoVoid
    {
        $doVoid = new DoVoid();
        $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/void', RequestUriV1::AUTHORIZATION_RESOURCE, $authorizationId),
            $doVoid
        );

        $doVoid->assign($response);

        return $doVoid;
    }
}
