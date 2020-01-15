<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use GuzzleHttp\Exception\ClientException;
use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\PayPal\Api\Patch;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;

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

    /**
     * @throws ClientException
     * @throws WebhookAlreadyExistsException
     */
    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, ?string $salesChannelId): string
    {
        try {
            $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPostRequest(
                RequestUri::WEBHOOK_RESOURCE,
                $createWebhooks
            );

            return $response['id'];
        } catch (ClientException $e) {
            $error = $this->getErrorFromResponse($e);

            if ($this->checkForErrorName($error, 'WEBHOOK_URL_ALREADY_EXISTS')) {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            throw $e;
        }
    }

    /**
     * @throws ClientException
     * @throws WebhookIdInvalidException
     */
    public function getWebhookUrl(string $webhookId, ?string $salesChannelId): string
    {
        try {
            $response = $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendGetRequest(
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId
            );

            return $response['url'];
        } catch (ClientException $e) {
            $error = $this->getErrorFromResponse($e);

            if ($this->checkForErrorName($error, 'INVALID_RESOURCE_ID')) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw $e;
        }
    }

    /**
     * @throws ClientException
     * @throws WebhookIdInvalidException
     */
    public function updateWebhook(string $webhookUrl, string $webhookId, ?string $salesChannelId): void
    {
        $requestData = [];
        $patchData = [
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/url',
            'value' => $webhookUrl,
        ];
        $patch = new Patch();
        $patch->assign($patchData);
        $requestData[] = $patch;

        try {
            $this->payPalClientFactory->createPaymentClient($salesChannelId)->sendPatchRequest(
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId,
                $requestData
            );
        } catch (ClientException $e) {
            $error = $this->getErrorFromResponse($e);

            if ($this->checkForErrorName($error, 'INVALID_RESOURCE_ID')) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw $e;
        }
    }

    private function getErrorFromResponse(ClientException $exception): array
    {
        $response = $exception->getResponse();
        if ($response === null) {
            throw $exception;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function checkForErrorName(array $error, string $errorName): bool
    {
        return isset($error['name']) && $error['name'] === $errorName;
    }
}
