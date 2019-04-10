<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Client;

use GuzzleHttp\Client;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\RequestUri;

class TokenClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(OAuthCredentials $credentials, string $url)
    {
        $this->client = new Client([
            'base_uri' => $url,
            'headers' => [
                'PayPal-Partner-Attribution-Id' => PartnerAttributionId::PAYPAL_CLASSIC,
                'Authorization' => (string) $credentials,
            ],
        ]);
    }

    public function get(): array
    {
        $data = [
            'form_params' => [
                'grant_type' => 'client_credentials',
            ],
        ];

        $response = $this->client->post(RequestUri::TOKEN_RESOURCE, $data)->getBody()->getContents();

        return json_decode($response, true);
    }
}
