<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Resource;

use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Gateway\WebhookGateway;
use Shopware\PayPalSDK\Struct\V1\Patch;
use Shopware\PayPalSDK\Struct\V1\PatchCollection;
use Shopware\PayPalSDK\Struct\V1\Webhook;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\Exception\WebhookValidationError;

#[Package('checkout')]
class WebhookResource
{
    private const INVALID_WEBHOOK_ID_ERROR_NAME = 'INVALID_RESOURCE_ID';
    private const WEBHOOK_URL_EXISTS_ERROR_NAME = 'WEBHOOK_URL_ALREADY_EXISTS';
    private const WEBHOOK_URL_VALIDATION_ERROR_NAME = 'VALIDATION_ERROR';

    /**
     * @internal
     */
    public function __construct(
        private readonly WebhookGateway $webhookGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
    ) {
    }

    /**
     * @throws PayPalApiException
     * @throws WebhookAlreadyExistsException
     */
    public function createWebhook(string $webhookUrl, Webhook $webhook, ?string $salesChannelId): string
    {
        $context = $this->apiContextFactory->getApiContext($salesChannelId);

        try {
            return $this->webhookGateway->createWebhook($webhook, $context)->getId();
        } catch (PayPalApiException $e) {
            if ($e->is(self::WEBHOOK_URL_EXISTS_ERROR_NAME)) {
                throw new WebhookAlreadyExistsException($webhookUrl);
            }

            if ($e->is(self::WEBHOOK_URL_VALIDATION_ERROR_NAME)) {
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
        $context = $this->apiContextFactory->getApiContext($salesChannelId);

        try {
            return $this->webhookGateway->getWebhook($webhookId, $context)->getUrl();
        } catch (PayPalApiException $e) {
            if ($e->is(self::INVALID_WEBHOOK_ID_ERROR_NAME)) {
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
        $context = $this->apiContextFactory->getApiContext($salesChannelId);

        $patches = PatchCollection::createFromAssociative([[
            'op' => Patch::OPERATION_REPLACE,
            'path' => '/url',
            'value' => $webhookUrl,
        ]]);

        try {
            $this->webhookGateway->updateWebhook($webhookId, $patches, $context);
        } catch (PayPalApiException $e) {
            if ($e->is(self::INVALID_WEBHOOK_ID_ERROR_NAME)) {
                throw new WebhookIdInvalidException($webhookId);
            }

            if ($e->is(self::WEBHOOK_URL_VALIDATION_ERROR_NAME)) {
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
        $context = $this->apiContextFactory->getApiContext($salesChannelId);

        try {
            $this->webhookGateway->deleteWebhook($webhookId, $context);
        } catch (PayPalApiException $e) {
            if ($e->is(self::INVALID_WEBHOOK_ID_ERROR_NAME)) {
                throw new WebhookIdInvalidException($webhookId);
            }

            throw $e;
        }
    }
}
