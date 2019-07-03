<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Client;

use GuzzleHttp\Client;
use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\OAuthCredentials;
use Swag\PayPal\PayPal\BaseURL;
use Swag\PayPal\PayPal\PartnerAttributionId;
use Swag\PayPal\PayPal\Resource\TokenResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;

class PayPalClient
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    /**
     * @var Client
     */
    private $client;

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function __construct(
        TokenResource $tokenResource,
        SwagPayPalSettingStruct $settings,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ) {
        $this->tokenResource = $tokenResource;

        $url = $settings->getSandbox() ? BaseURL::SANDBOX : BaseURL::LIVE;

        $clientId = $settings->getClientId();
        $clientSecret = $settings->getClientSecret();

        if ($clientId === '') {
            throw new PayPalSettingsInvalidException('clientId');
        }

        if ($clientSecret === '') {
            throw new PayPalSettingsInvalidException('clientSecret');
        }

        $credentials = $this->createCredentialsObject($clientId, $clientSecret);
        $authorizationHeader = $this->createAuthorizationHeaderValue($credentials, $url);

        $this->client = new Client([
            'base_uri' => $url,
            'headers' => [
                'PayPal-Partner-Attribution-Id' => $partnerAttributionId,
                'Authorization' => $authorizationHeader,
            ],
        ]);
    }

    public function sendPostRequest(string $resourceUri, PayPalStruct $data): array
    {
        $options = [
            'headers' => ['content-type' => 'application/json'],
            'json' => $data,
        ];
        $response = $this->client->post($resourceUri, $options)->getBody()->getContents();

        return $this->decodeJsonResponse($response);
    }

    public function sendGetRequest(string $resourceUri): array
    {
        $response = $this->client->get($resourceUri)->getBody()->getContents();

        return $this->decodeJsonResponse($response);
    }

    /**
     * @param PayPalStruct[] $data
     */
    public function sendPatchRequest(string $resourceUri, array $data): array
    {
        $options = [
            'headers' => ['content-type' => 'application/json'],
            'json' => $data,
        ];
        $response = $this->client->patch($resourceUri, $options)->getBody()->getContents();

        return $this->decodeJsonResponse($response);
    }

    private function createCredentialsObject(string $clientId, string $clientSecret): OAuthCredentials
    {
        $credentials = new OAuthCredentials();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);

        return $credentials;
    }

    private function createAuthorizationHeaderValue(OAuthCredentials $credentials, string $url): string
    {
        $token = $this->tokenResource->getToken($credentials, $url);

        return $token->getTokenType() . ' ' . $token->getAccessToken();
    }

    private function decodeJsonResponse(string $response): array
    {
        return json_decode($response, true);
    }
}
