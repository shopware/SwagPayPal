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
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\Resource\TokenResourceInterface;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class PayPalClient extends AbstractClient implements PayPalClientInterface
{
    private TokenResourceInterface $tokenResource;

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function __construct(
        TokenResourceInterface $tokenResource,
        LoggerInterface $logger,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
        ?OAuthCredentials $credentials = null
    ) {
        $this->tokenResource = $tokenResource;

        if ($credentials === null) {
            throw new \RuntimeException('Credentials have to be provided');
        }

        $authorizationHeader = $this->createAuthorizationHeaderValue($credentials);

        $client = new Client([
            'base_uri' => $credentials->getUrl(),
            'headers' => [
                'PayPal-Partner-Attribution-Id' => $partnerAttributionId,
                'Authorization' => $authorizationHeader,
            ],
        ]);

        parent::__construct($client, $logger);
    }

    public function sendPostRequest(string $resourceUri, ?PayPalApiStruct $data, array $headers = []): array
    {
        $headers['content-type'] = 'application/json';
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->post($resourceUri, $options);
    }

    public function sendGetRequest(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
        ];

        return $this->get($resourceUri, $options);
    }

    /**
     * @param PayPalApiStruct[] $data
     */
    public function sendPatchRequest(string $resourceUri, array $data, array $headers = []): array
    {
        $headers['content-type'] = 'application/json';
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->patch($resourceUri, $options);
    }

    public function sendDeleteRequest(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
        ];

        return $this->delete($resourceUri, $options);
    }

    private function createAuthorizationHeaderValue(OAuthCredentials $credentials): string
    {
        $token = $this->tokenResource->getToken($credentials);

        return \sprintf('%s %s', $token->getTokenType(), $token->getAccessToken());
    }
}
