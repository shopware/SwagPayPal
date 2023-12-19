<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\Client\PayPalClientFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\CreateWebhooks;
use Swag\PayPal\RestApi\V1\Api\Patch;
use Swag\PayPal\RestApi\V1\RequestUriV1;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\Exception\WebhookValidationError;

#[Package('checkout')]
class WebhookResource
{
    private const INVALID_WEBHOOK_ID_ERROR_NAME = 'INVALID_RESOURCE_ID';
    private const WEBHOOK_URL_EXISTS_ERROR_NAME = 'WEBHOOK_URL_ALREADY_EXISTS';
    private const WEBHOOK_URL_VALIDATION_ERROR_NAME = 'VALIDATION_ERROR';

    private PayPalClientFactoryInterface $payPalClientFactory;

    /**
     * @internal
     */
    public function __construct(PayPalClientFactoryInterface $payPalClientFactory)
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
                RequestUriV1::WEBHOOK_RESOURCE,
                $createWebhooks
            );

            return $response['id'];
        } catch (PayPalApiException $e) {
            if ($e->getParameters()['name'] === self::WEBHOOK_URL_EXISTS_ERROR_NAME) {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            if ($e->getParameters()['name'] === self::WEBHOOK_URL_VALIDATION_ERROR_NAME) {
                throw new WebhookValidationError($webhookUrl);
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
                \sprintf('%s/%s', RequestUriV1::WEBHOOK_RESOURCE, $webhookId)
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
                \sprintf('%s/%s', RequestUriV1::WEBHOOK_RESOURCE, $webhookId),
                $requestData
            );
        } catch (PayPalApiException $e) {
            if ($e->getParameters()['name'] === self::INVALID_WEBHOOK_ID_ERROR_NAME) {
                throw new WebhookIdInvalidException($webhookId);
            }

            if ($e->getParameters()['name'] === self::WEBHOOK_URL_VALIDATION_ERROR_NAME) {
                throw new WebhookValidationError($webhookUrl);
            }

            throw $e;
        }
    }

    /**
     * @throws PayPalApiException
     * @throws WebhookIdInvalidException
     */
    public function deleteWebhook(string $webhookId, ?string $salesChannelId): void
    {
        try {
            $this->payPalClientFactory->getPayPalClient($salesChannelId)->sendDeleteRequest(
                \sprintf('%s/%s', RequestUriV1::WEBHOOK_RESOURCE, $webhookId)
            );
        } catch (PayPalApiException $e) {
            if ($e->getParameters()['name'] === self::INVALID_WEBHOOK_ID_ERROR_NAME) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw $e;
        }
    }
}
