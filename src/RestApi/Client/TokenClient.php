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
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\OAuthCredentials;
use Swag\PayPal\RestApi\V1\RequestUriV1;

#[Package('checkout')]
class TokenClient extends AbstractClient implements TokenClientInterface
{
    public function __construct(
        OAuthCredentials $credentials,
        LoggerInterface $logger,
    ) {
        $client = new Client([
            'base_uri' => $credentials->getUrl(),
            'headers' => [
                'PayPal-Partner-Attribution-Id' => PartnerAttributionId::PAYPAL_PPCP,
                'Authorization' => (string) $credentials,
            ],
        ]);

        parent::__construct($client, $logger);
    }

    /**
     * @param array<string, string> $additionalData
     */
    public function getToken(array $additionalData = []): array
    {
        $data = [
            'form_params' => [
                'grant_type' => 'client_credentials',
                ...$additionalData,
            ],
        ];

        return $this->post(RequestUriV1::TOKEN_RESOURCE, $data);
    }
}
