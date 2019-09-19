<?php declare(strict_types=1);

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Webhook;

interface WebhookHandler
{
    /**
     * Returns the name of the webhook event. Defines which webhook event this handler could handle
     *
     * @see WebhookEventTypes
     */
    public function getEventType(): string;

    /**
     * Invokes the webhook using the provided data.
     */
    public function invoke(Webhook $webhook, Context $context): void;
}
