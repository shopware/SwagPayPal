<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\RequestUriV1;

class TokenClient extends AbstractClient
{
    public function __construct(
        OAuthCredentials $credentials,
        LoggerInterface $logger
    ) {
        $client = new Client([
            'base_uri' => $credentials->getUrl(),
            'headers' => [
                'PayPal-Partner-Attribution-Id' => PartnerAttributionId::PAYPAL_CLASSIC,
                'Authorization' => (string) $credentials,
            ],
        ]);

        parent::__construct($client, $logger);
    }

    public function getToken(): array
    {
        $data = [
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ];

        return $this->post(RequestUriV1::TOKEN_RESOURCE, $data);
    }
}
