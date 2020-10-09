<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\PayPal\Resource;

use Swag\PayPal\Payment\Exception\PayPalApiException;
use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\PayPal\Api\Patch;
use Swag\PayPal\PayPal\Client\PayPalClientFactory;
use Swag\PayPal\PayPal\RequestUri;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;

class WebhookResource
{
    private const INVALID_WEBHOOK_ID_ERROR_NAME = 'INVALID_RESOURCE_ID';
    private const WEBHOOK_URL_EXISTS_ERROR_NAME = 'WEBHOOK_URL_ALREADY_EXISTS';

    /**
     * @var PayPalClientFactory
     */
    private $payPalClientFactory;

    public function __construct(PayPalClientFactory $payPalClientFactory)
    {
        $this->payPalClientFactory = $payPalClientFactory;
    }

    /**
     * @throws PayPalApiException
     * @throws WebhookAlreadyExistsException
     */
    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, ?string $salesChannelId): string
    {
        try {
            $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPostRequest(
                RequestUri::WEBHOOK_RESOURCE,
                $createWebhooks
            );

            return $response['id'];
        } catch (PayPalApiException $e) {
            if ($e->getParameters()['name'] === self::WEBHOOK_URL_EXISTS_ERROR_NAME) {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            throw $e;
        }
    }

    /**
     * @throws PayPalApiException
     * @throws WebhookIdInvalidException
     */
    public function getWebhookUrl(string $webhookId, ?string $salesChannelId): string
    {
        try {
            $response = $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendGetRequest(
                \sprintf('%s/%s', RequestUri::WEBHOOK_RESOURCE, $webhookId)
            );

            return $response['url'];
        } catch (PayPalApiException $e) {
            if ($e->getParameters()['name'] === self::INVALID_WEBHOOK_ID_ERROR_NAME) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw $e;
        }
    }

    /**
     * @throws PayPalApiException
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
            $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendPatchRequest(
                \sprintf('%s/%s', RequestUri::WEBHOOK_RESOURCE, $webhookId),
                $requestData
            );
        } catch (PayPalApiException $e) {
            if ($e->getParameters()['name'] === self::INVALID_WEBHOOK_ID_ERROR_NAME) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw $e;
        }
    }
}
