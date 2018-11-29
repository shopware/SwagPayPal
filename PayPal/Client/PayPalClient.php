<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Client;

use GuzzleHttp\Client;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\OAuthCredentials;
use SwagPayPal\PayPal\Api\PayPalStruct;
use SwagPayPal\PayPal\BaseURL;
use SwagPayPal\PayPal\Client\Exception\PayPalSettingsInvalidException;
use SwagPayPal\PayPal\PartnerAttributionId;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Setting\SwagPayPalSettingGeneralStruct;

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
        Context $context,
        SwagPayPalSettingGeneralStruct $settings,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC
    ) {
        $this->tokenResource = $tokenResource;

        $url = $settings->getSandbox() ? BaseURL::SANDBOX : BaseURL::LIVE;

        $clientId = $settings->getClientId();
        $clientSecret = $settings->getClientSecret();

        if ($clientId === null || $clientId === '') {
            throw new PayPalSettingsInvalidException('clientId');
        }

        if ($clientSecret === null || $clientSecret === '') {
            throw new PayPalSettingsInvalidException('clientSecret');
        }

        //Create authentication
        $credentials = new OAuthCredentials();
        $credentials->setRestId($clientId);
        $credentials->setRestSecret($clientSecret);

        $authHeader = $this->createAuthentication($credentials, $context, $url);

        $this->client = new Client([
            'base_uri' => $url,
            'headers' => [
                'PayPal-Partner-Attribution-Id' => $partnerAttributionId,
                'Authorization' => $authHeader,
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

    /**
     * Creates the authentication header for the PayPal API.
     * If there is no cached token yet, it will be generated on the fly.
     */
    private function createAuthentication(OAuthCredentials $credentials, Context $context, string $url): string
    {
        $token = $this->tokenResource->getToken($credentials, $context, $url);

        return $token->getTokenType() . ' ' . $token->getAccessToken();
    }

    private function decodeJsonResponse(string $response): array
    {
        return json_decode($response, true);
    }
}
