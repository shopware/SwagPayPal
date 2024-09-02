<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Webhook;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\Webhook\Exception\WebhookException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookHandlerNotFoundException;

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
    public function __construct(\IteratorAggregate $webhooks)
    {
        foreach ($webhooks as $webhook) {
            $this->registerWebhook($webhook);
        }
    }

    /**
     * @see WebhookEventNames
     *
     * @throws WebhookHandlerNotFoundException
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
        $webhookEventName = $webhook->getEventName();
        if (isset($this->registeredWebhooks[$webhookEventName])) {
            throw new WebhookException($webhookEventName, 'The specified event is already registered.');
        }

        $this->registeredWebhooks[$webhookEventName] = $webhook;
    }
}
