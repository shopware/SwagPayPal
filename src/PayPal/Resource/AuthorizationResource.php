<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\PayPal\Api\Capture;
use Swag\PayPal\PayPal\Api\DoVoid;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource\Authorization;
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

    public function get(string $authorizationId, string $salesChannelId): Authorization
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendGetRequest(
            \sprintf('%s/%s', RequestUri::AUTHORIZATION_RESOURCE, $authorizationId)
        );

        return (new Authorization())->assign($response);
    }

    public function capture(string $authorizationId, Capture $capture, string $salesChannelId): Capture
    {
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/capture', RequestUri::AUTHORIZATION_RESOURCE, $authorizationId),
            $capture
        );

        $capture->assign($response);

        return $capture;
    }

    public function void(string $authorizationId, string $salesChannelId): DoVoid
    {
        $doVoid = new DoVoid();
        $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
            \sprintf('%s/%s/void', RequestUri::AUTHORIZATION_RESOURCE, $authorizationId),
            $doVoid
        );

        $doVoid->assign($response);

        return $doVoid;
    }
}
