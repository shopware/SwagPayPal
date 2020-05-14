<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Common\IZettleStruct;
use Swag\PayPal\IZettle\Api\Error\IZettleApiError;
use Swag\PayPal\IZettle\Api\Exception\IZettleApiException;
use Swag\PayPal\IZettle\Api\OAuthCredentials;
use Swag\PayPal\IZettle\Resource\TokenResource;

class IZettleClient extends AbstractClient
{
    /**
     * @var TokenResource
     */
    private $tokenResource;

    public function __construct(string $baseUri, TokenResource $tokenResource, OAuthCredentials $credentials, LoggerInterface $logger)
    {
        $this->tokenResource = $tokenResource;

        $authorizationHeader = $this->createAuthorizationHeaderValue($credentials);

        $client = new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => $authorizationHeader,
            ],
        ]);

        parent::__construct($client, $logger);
    }

    public function sendPostRequest(string $resourceUri, IZettleStruct $data): ?array
    {
        $options = [
            'json' => $data,
        ];

        return $this->post($resourceUri, $options);
    }

    public function sendPutRequest(string $resourceUri, IZettleStruct $data): ?array
    {
        $options = [
            'json' => $data,
            'headers' => [
                'If-Match' => '*',
            ],
        ];

        return $this->put($resourceUri, $options);
    }

    public function sendGetRequest(string $resourceUri, ?IZettleStruct $data = null): ?array
    {
        if ($data === null) {
            $this->get($resourceUri);
        }

        $options = [
            'json' => $data,
        ];

        return $this->get($resourceUri, $options);
    }

    protected function handleError(array $error): void
    {
        $errorStruct = new IZettleApiError();
        $errorStruct->assign($error);

        throw new IZettleApiException($errorStruct);
    }

    private function createAuthorizationHeaderValue(OAuthCredentials $credentials): string
    {
        $token = $this->tokenResource->getToken($credentials);

        return 'Bearer ' . $token->getAccessToken();
    }
}
