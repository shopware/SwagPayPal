<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Swag\PayPal\IZettle\Api\Error\IZettleTokenError;
use Swag\PayPal\IZettle\Api\Exception\IZettleTokenException;
use Swag\PayPal\IZettle\Api\IZettleBaseURL;
use Swag\PayPal\IZettle\Api\IZettleRequestUri;
use Swag\PayPal\IZettle\Api\OAuthCredentials;

class TokenClient extends AbstractClient
{
    public function __construct(LoggerInterface $logger)
    {
        $client = new Client([
            'base_uri' => IZettleBaseURL::OAUTH,
        ]);

        parent::__construct($client, $logger);
    }

    public function getToken(OAuthCredentials $credentials): array
    {
        // TODO: Refactor to API key auth
        $data = [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => $credentials->getClientId(),
                'username' => $credentials->getUsername(),
                'password' => $credentials->getPassword(),
            ],
        ];

        return $this->post(IZettleRequestUri::TOKEN_RESOURCE, $data);
    }

    protected function handleError(array $error): void
    {
        $errorStruct = new IZettleTokenError();
        $errorStruct->assign($error);

        throw new IZettleTokenException($errorStruct);
    }
}
