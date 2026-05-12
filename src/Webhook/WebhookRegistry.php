<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Webhook\Exception\WebhookException;
use Swag\PayPal\Webhook\Exception\WebhookHandlerNotFoundException;

#[Package('checkout')]
class WebhookRegistry
{
    /**
     * @var WebhookHandler[]
     */
    private array $registeredWebhooks;

    /**
     * @internal
     */
    public function __construct(iterable $webhooks)
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
            throw new WebhookHandlerNotFoundException($eventType);
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
