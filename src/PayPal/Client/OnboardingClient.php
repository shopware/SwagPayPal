<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Client;

use GuzzleHttp\Client;

class OnboardingClient
{
    /**
     * @var Client
     */
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getClientCredentials(
        string $authCode,
        string $sharedId,
        string $nonce,
        string $url,
        string $partnerId
    ): array {
        $accessToken = $this->getAccesToken($authCode, $sharedId, $nonce, $url);

        return $this->getCredentials($accessToken, $url, $partnerId);
    }

    private function getAccesToken(string $authCode, string $sharedId, string $nonce, string $uri): string
    {
        $url = $uri . 'oauth2/token';
        $response = $this->client->request('POST', $url, [
            'headers' => ['content-type' => 'text/plain'],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'code_verifier' => $nonce,
            ],
            'auth' => [$sharedId, ''],
        ])->getBody()->getContents();

        $response = $this->decodeJsonResponse($response);

        return $response['access_token'];
    }

    private function getCredentials(string $accessToken, string $uri, string $partnerId): array
    {
        $url = sprintf('%scustomer/partners/%s/merchant-integrations/credentials/', $uri, $partnerId);
        $response = $this->client->request('GET', $url, [
            'headers' => [
                'content-type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
            ],
        ])->getBody()->getContents();

        $response = $this->decodeJsonResponse($response);

        return $response;
    }

    private function decodeJsonResponse(string $response): array
    {
        return json_decode($response, true);
    }
}
