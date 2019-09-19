<?php declare(strict_types=1);

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Webhook\Exception\WebhookException;

interface WebhookServiceInterface
{
    public function registerWebhook(?string $salesChannelId): string;

    /**
     * @throws WebhookException if no transaction could be found to the given Webhook
     */
    public function executeWebhook(Webhook $webhook, Context $context): void;
}
