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
use SwagPayPal\PayPal\BaseURL;
use SwagPayPal\PayPal\Client\Exception\PayPalSettingsInvalidException;
use SwagPayPal\PayPal\Client\Exception\UnsupportedHttpMethodException;
use SwagPayPal\PayPal\PartnerAttributionId;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\PayPal\Struct\OAuthCredentials;
use SwagPayPal\Setting\SwagPayPalSettingGeneralStruct;
use Symfony\Component\HttpFoundation\Request;

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
        $credentials->setRestId($settings->getClientId());
        $credentials->setRestSecret($settings->getClientSecret());

        $authHeader = $this->createAuthentication($credentials, $context, $url);

        $this->client = new Client([
            'base_uri' => $url,
            'headers' => [
                'PayPal-Partner-Attribution-Id' => $partnerAttributionId,
                'Authorization' => $authHeader,
            ],
        ]);
    }

    /**
     * Sends a request and returns the response.
     * The type can be obtained from RequestType.php
     *
     * @throws UnsupportedHttpMethodException
     */
    public function sendRequest(string $method, string $resourceUri, array $data = []): array
    {
        $options = [];
        if (!empty($data)) {
            $options['headers'] = ['content-type' => 'application/json'];
            $options['json'] = $data;
        }

        switch ($method) {
            case Request::METHOD_POST:
                $response = $this->client->post($resourceUri, $options)->getBody()->getContents();
                break;

            case Request::METHOD_GET:
                $response = $this->client->get($resourceUri, $options)->getBody()->getContents();
                break;

            case Request::METHOD_PATCH:
                $response = $this->client->patch($resourceUri, $options)->getBody()->getContents();
                break;

            case Request::METHOD_PUT:
                $response = $this->client->put($resourceUri, $options)->getBody()->getContents();
                break;

            case Request::METHOD_HEAD:
                $response = $this->client->head($resourceUri, $options)->getBody()->getContents();
                break;

            case Request::METHOD_DELETE:
                $response = $this->client->delete($resourceUri, $options)->getBody()->getContents();
                break;

            default:
                throw new UnsupportedHttpMethodException($method);
        }

        return json_decode($response, true);
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
}
