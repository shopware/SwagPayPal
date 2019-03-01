<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook;

use SwagPayPal\Webhook\Exception\WebhookException;

class WebhookRegistry
{
    /**
     * @var WebhookHandler[]
     */
    private $registeredWebhooks;

    public function __construct(\IteratorAggregate $webhooks)
    {
        foreach ($webhooks as $webhook) {
            $this->registerWebhook($webhook);
        }
    }

    /**
     * @see WebhookEventTypes
     *
     * @throws WebhookException
     */
    public function getWebhookHandler(string $eventType): WebhookHandler
    {
        if (!isset($this->registeredWebhooks[$eventType])) {
            throw new WebhookException($eventType, 'The specified event-type does not exist.');
        }

        return $this->registeredWebhooks[$eventType];
    }

    /**
     * @throws WebhookException
     */
    private function registerWebhook(WebhookHandler $webhook): void
    {
        $webhookEventType = $webhook->getEventType();
        if (isset($this->registeredWebhooks[$webhookEventType])) {
            throw new WebhookException($webhookEventType, 'The specified event is already registered.');
        }

        $this->registeredWebhooks[$webhookEventType] = $webhook;
    }
}
