<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Api\Authentication\OAuthCredentials;
use Swag\PayPal\Pos\Api\Common\PosStruct;
use Swag\PayPal\Pos\Api\Error\PosApiError;
use Swag\PayPal\Pos\Api\Exception\PosApiException;
use Swag\PayPal\Pos\Api\Exception\PosException;
use Swag\PayPal\Pos\Resource\TokenResource;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class PosClient extends AbstractClient
{
    private TokenResource $tokenResource;

    public function __construct(
        string $baseUri,
        TokenResource $tokenResource,
        OAuthCredentials $credentials,
        LoggerInterface $logger,
    ) {
        $this->tokenResource = $tokenResource;

        $authorizationHeader = $this->createAuthorizationHeaderValue($credentials);

        $client = new Client([
            'base_uri' => $baseUri,
            'headers' => [
                'Authorization' => $authorizationHeader,
                'X-iZettle-Application-Id' => SwagPayPal::POS_PARTNER_CLIENT_ID,
            ],
        ]);

        parent::__construct($client, $logger);
    }

    public function sendPostRequest(string $resourceUri, PosStruct $data): ?array
    {
        $options = [
            'json' => $data,
        ];

        return $this->post($resourceUri, $options);
    }

    public function sendDeleteRequest(string $resourceUri, ?string $query = null): ?array
    {
        if ($query === null) {
            return $this->delete($resourceUri);
        }

        $options = [
            'headers' => ['Content-Type' => 'application/json'],
            'query' => $query,
        ];

        return $this->delete($resourceUri, $options);
    }

    public function sendPutRequest(string $resourceUri, PosStruct $data): ?array
    {
        $options = [
            'json' => $data,
            'headers' => [
                'If-Match' => '*',
            ],
        ];

        return $this->put($resourceUri, $options);
    }

    public function sendGetRequest(string $resourceUri, ?PosStruct $data = null): ?array
    {
        if ($data === null) {
            return $this->get($resourceUri);
        }

        $options = [
            'json' => $data,
        ];

        return $this->get($resourceUri, $options);
    }

    protected function handleError(RequestException $requestException, array $error): PosException
    {
        $errorStruct = new PosApiError();
        $errorStruct->assign($error);

        return new PosApiException($errorStruct, (int) $requestException->getCode());
    }

    private function createAuthorizationHeaderValue(OAuthCredentials $credentials): string
    {
        $token = $this->tokenResource->getToken($credentials);

        return 'Bearer ' . $token->getAccessToken();
    }
}
