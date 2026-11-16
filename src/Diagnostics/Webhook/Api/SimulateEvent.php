<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook\Api;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\Struct;

/**
 * PayPal requires either the webhook ID or the URL, not both.
 *
 * @internal
 */
// An explicit `required` isn't converted to snake case like the properties, so it names the wire keys
#[OA\Schema(schema: 'swag_paypal_diagnostics_webhook_simulate_event', required: ['event_type', 'resource_version'])]
#[Package('checkout')]
class SimulateEvent extends Struct
{
    #[OA\Property(description: 'The ID of a registered webhook, PayPal sends the event to its URL. Either this or `url` must be set', type: 'string')]
    protected string $webhookId;

    #[OA\Property(description: 'The URL PayPal sends the event to. Either this or `webhook_id` must be set', type: 'string')]
    protected string $url;

    #[OA\Property(type: 'string')]
    protected string $eventType;

    #[OA\Property(type: 'string')]
    protected string $resourceVersion;

    public function getWebhookId(): string
    {
        return $this->webhookId;
    }

    public function setWebhookId(string $webhookId): void
    {
        $this->webhookId = $webhookId;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): void
    {
        $this->eventType = $eventType;
    }

    public function getResourceVersion(): string
    {
        return $this->resourceVersion;
    }

    public function setResourceVersion(string $resourceVersion): void
    {
        $this->resourceVersion = $resourceVersion;
    }
}
