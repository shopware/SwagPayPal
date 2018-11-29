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
use SwagPayPal\PayPal\Api\CreateWebhooks;
use SwagPayPal\PayPal\Api\Patch;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\RequestUri;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;

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
    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, Context $context): string
    {
        try {
            $response = $this->payPalClientFactory->createPaymentClient($context)->sendPostRequest(
                RequestUri::WEBHOOK_RESOURCE,
                $createWebhooks
            );

            return $response['id'];
        } catch (ClientException $e) {
            $error = $this->getErrorFromResponse($e);

            if ($this->checkForErrorName($error, 'WEBHOOK_URL_ALREADY_EXISTS')) {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            throw  $e;
        }
    }

    /**
     * @throws ClientException
     * @throws WebhookIdInvalidException
     */
    public function getWebhookUrl(string $webhookId, Context $context): string
    {
        try {
            $response = $this->payPalClientFactory->createPaymentClient($context)->sendGetRequest(
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId
            );

            return $response['url'];
        } catch (ClientException $e) {
            $error = $this->getErrorFromResponse($e);

            if ($this->checkForErrorName($error, 'INVALID_RESOURCE_ID')) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw  $e;
        }
    }

    /**
     * @throws ClientException
     * @throws WebhookIdInvalidException
     */
    public function updateWebhook(string $webhookUrl, string $webhookId, Context $context): void
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
            $this->payPalClientFactory->createPaymentClient($context)->sendPatchRequest(
                RequestUri::WEBHOOK_RESOURCE . '/' . $webhookId,
                $requestData
            );
        } catch (ClientException $e) {
            $error = $this->getErrorFromResponse($e);

            if ($this->checkForErrorName($error, 'INVALID_RESOURCE_ID')) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw  $e;
        }
    }

    /**
     * @throws ClientException
     */
    private function getErrorFromResponse(ClientException $e): array
    {
        $response = $e->getResponse();
        if ($response === null) {
            throw $e;
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function checkForErrorName(array $error, string $errorName): bool
    {
        return isset($error['name']) && $error['name'] === $errorName;
    }
}
