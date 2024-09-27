<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\Client;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class CredentialsClient extends AbstractClient
{
    public function __construct(
        string $url,
        LoggerInterface $logger,
    ) {
        $client = new Client(['base_uri' => $url]);

        parent::__construct($client, $logger);
    }

    public function getAccessToken(string $authCode, string $sharedId, string $nonce): string
    {
        $options = [
            'headers' => ['content-type' => 'text/plain'],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $authCode,
                'code_verifier' => $nonce,
            ],
            'auth' => [$sharedId, ''],
        ];

        $response = $this->post(RequestUriV1::TOKEN_RESOURCE, $options);

        return $response['access_token'];
    }

    public function getCredentials(string $accessToken, string $partnerId): array
    {
        $url = \sprintf(RequestUriV1::CREDENTIALS_RESOURCE, $partnerId);
        $options = [
            'headers' => [
                'content-type' => 'application/json',
                'Authorization' => \sprintf('Bearer %s', $accessToken),
            ],
        ];

        return $this->get($url, $options);
    }
}
