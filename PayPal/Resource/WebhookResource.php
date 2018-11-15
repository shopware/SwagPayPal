<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\PayPal\Resource;

use GuzzleHttp\Exception\ClientException;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\Component\Patch\PatchInterface;
use SwagPayPal\PayPal\RequestUri;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;
use Symfony\Component\HttpFoundation\Request;

class WebhookResource
{
    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    public function createWebhook(string $webhookUrl, array $webhookEvents, Context $context): string
    {
        $requestData = [
            'url' => $webhookUrl,
            'event_types' => [],
        ];

        foreach ($webhookEvents as $event) {
            $requestData['event_types'][] = [
                'name' => $event,
            ];
        }

        try {
            $response = $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
                Request::METHOD_POST,
                RequestUri::WEBHOOK_RESOURCE,
                $requestData
            );

            return $response['id'];
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw $e;
            }

            $error = json_decode($response->getBody()->getContents(), true);

            if ($error['name'] === 'WEBHOOK_URL_ALREADY_EXISTS') {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            throw  $e;
        }
    }

    public function getWebhookUrl(string $webhookId, Context $context): string
    {
        try {
            $response = $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
                Request::METHOD_GET,
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId
            );

            return $response['url'];
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw $e;
            }

            $error = json_decode($response->getBody()->getContents(), true);

            if ($error['name'] === 'INVALID_RESOURCE_ID') {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw  $e;
        }
    }

    public function updateWebhook(string $webhookUrl, string $webhookId, Context $context): void
    {
        $requestData = [];
        $requestData[] = [
            'op' => PatchInterface::OPERATION_REPLACE,
            'path' => '/url',
            'value' => $webhookUrl,
        ];

        try {
            $this->payPalClientFactory->createPaymentClient($context)->sendRequest(
                Request::METHOD_PATCH,
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId,
                $requestData
            );
        } catch (ClientException $e) {
            $response = $e->getResponse();
            if ($response === null) {
                throw $e;
            }

            $error = json_decode($response->getBody()->getContents(), true);

            if ($error['name'] === 'INVALID_RESOURCE_ID') {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw  $e;
        }
    }
}
