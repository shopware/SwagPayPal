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
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;

/**
 * @internal
 */
#[OA\Schema(schema: 'swag_paypal_diagnostics_webhook_event_list')]
#[Package('checkout')]
class WebhookEventList extends Struct
{
    #[OA\Property(type: 'array', items: new OA\Items(ref: Event::class))]
    protected WebhookEventCollection $events;

    public function getEvents(): WebhookEventCollection
    {
        // PayPal leaves the list out when there are no events
        return $this->events ?? new WebhookEventCollection();
    }

    public function setEvents(WebhookEventCollection $events): void
    {
        $this->events = $events;
    }
}
