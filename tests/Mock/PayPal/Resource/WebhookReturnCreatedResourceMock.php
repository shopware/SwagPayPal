<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\PayPal\Resource\WebhookResource;
use Swag\PayPal\Webhook\WebhookService;

class WebhookReturnCreatedResourceMock extends WebhookResource
{
    public const CREATED_WEBHOOK_ID = 'createdWebhookId';

    public const THROW_WEBHOOK_ID_INVALID = 'webhookIdInvalid';

    public const THROW_WEBHOOK_ALREADY_EXISTS = 'webhookAlreadyExists';

    public const RETURN_CREATED_WEBHOOK_ID = 'returnCreatedWebhookId';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';
    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, ?string $salesChannelId): string
    {
        return self::CREATED_WEBHOOK_ID;
    }

    public function getWebhookUrl(string $webhookId, ?string $salesChannelId): string
    {
        return WebhookService::PAYPAL_WEBHOOK_ROUTE . '?'
            . WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME . '='
            . self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN;
    }

    public function updateWebhook(string $webhookUrl, string $webhookId, ?string $salesChannelId): void
    {
    }
}
