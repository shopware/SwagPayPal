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
use Swag\PayPal\Pos\Api\Authentification\OAuthCredentials;
use Swag\PayPal\Pos\Api\Error\PosTokenError;
use Swag\PayPal\Pos\Api\Exception\PosException;
use Swag\PayPal\Pos\Api\Exception\PosTokenException;
use Swag\PayPal\Pos\Api\PosBaseURL;
use Swag\PayPal\Pos\Api\PosRequestUri;
use Swag\PayPal\Pos\Api\Service\ApiKeyDecoder;
use Swag\PayPal\Pos\Setting\Exception\PosInvalidApiCredentialsException;

class TokenClient extends AbstractClient
{
    /**
     * @var ApiKeyDecoder
     */
    protected $apiKeyDecoder;

    public function __construct(LoggerInterface $logger, ApiKeyDecoder $apiKeyDecoder)
    {
        $client = new Client([
            'base_uri' => PosBaseURL::OAUTH,
            'headers' => [
                'X-iZettle-Application-Id' => self::PARTNER_IDENTIFIER,
            ],
        ]);

        $this->apiKeyDecoder = $apiKeyDecoder;
        parent::__construct($client, $logger);
    }

    public function getToken(OAuthCredentials $credentials): array
    {
        $clientId = $this->apiKeyDecoder->decode($credentials->getApiKey())->getPayload()->getClientId();

        $data = [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'client_id' => $clientId,
                'assertion' => $credentials->getApiKey(),
            ],
        ];

        $tokenResponse = $this->post(PosRequestUri::TOKEN_RESOURCE, $data);
        if ($tokenResponse === null) {
            throw new PosInvalidApiCredentialsException();
        }

        return $tokenResponse;
    }

    protected function handleError(RequestException $requestException, array $error): PosException
    {
        $errorStruct = new PosTokenError();
        $errorStruct->assign($error);

        return new PosTokenException($errorStruct, (int) $requestException->getCode());
    }
}
