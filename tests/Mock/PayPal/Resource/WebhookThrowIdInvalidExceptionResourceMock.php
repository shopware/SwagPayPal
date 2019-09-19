<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;

class WebhookThrowIdInvalidExceptionResourceMock extends WebhookReturnCreatedResourceMock
{
    public function getWebhookUrl(string $webhookId, ?string $salesChannelId): string
    {
        throw new WebhookIdInvalidException('');
    }

    public function updateWebhook(string $webhookUrl, string $webhookId, ?string $salesChannelId): void
    {
        throw new WebhookIdInvalidException('');
    }
}
