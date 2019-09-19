<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\PayPal\Resource;

use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;

class WebhookThrowAlreadyExistsExceptionResourceMock extends WebhookReturnCreatedResourceMock
{
    public function createWebhook(string $webhookUrl, CreateWebhooks $createWebhooks, ?string $salesChannelId): string
    {
        throw new WebhookAlreadyExistsException('');
    }
}
