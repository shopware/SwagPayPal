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
use Swag\PayPal\RestApi\PayPalApiStruct;

#[Package('checkout')]
class PayPalClient extends AbstractClient implements PayPalClientInterface
{
    public function __construct(
        array $credentials,
        string $baseUrl,
        LoggerInterface $logger,
        string $partnerAttributionId = PartnerAttributionId::PAYPAL_CLASSIC,
    ) {
        $client = new Client([
            'base_uri' => $baseUrl,
            'headers' => [
                'PayPal-Partner-Attribution-Id' => $partnerAttributionId,
                ...$credentials,
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

    public function sendPutRequest(string $resourceUri, PayPalApiStruct $data, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
            'json' => $data,
        ];

        return $this->put($resourceUri, $options);
    }

    public function sendDeleteRequest(string $resourceUri, array $headers = []): array
    {
        $options = [
            'headers' => $headers,
        ];

        return $this->delete($resourceUri, $options);
    }
}
