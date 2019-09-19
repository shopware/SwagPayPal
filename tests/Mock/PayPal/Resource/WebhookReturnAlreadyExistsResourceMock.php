<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Swag\PayPal\PayPal\Api\CreateWebhooks;

class WebhookReturnAlreadyExistsResourceMock extends WebhookReturnCreatedResourceMock
{
    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, ?string $salesChannelId): string
    {
        return self::ALREADY_EXISTING_WEBHOOK_ID;
    }
}
