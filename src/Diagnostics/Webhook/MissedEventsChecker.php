<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Swag\PayPal\Diagnostics\Webhook\Api\WebhookEventGateway;
use Swag\PayPal\Diagnostics\Webhook\Recording\LastWebhook;
use Swag\PayPal\RestApi\ApiContextFactoryInterface;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
class MissedEventsChecker
{
    // PayPal may still be delivering younger events
    private const GRACE_PERIOD = 'PT10M';

    private const PAGE_SIZE = 20;

    public function __construct(
        private readonly WebhookEventGateway $webhookEventGateway,
        private readonly ApiContextFactoryInterface $apiContextFactory,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * From the shop's receive time, as PayPal's mock events carry sample creation times far in the past.
     */
    public function check(string $salesChannelId, LastWebhook $lastWebhook): ?MissedEvents
    {
        try {
            // the API takes whole seconds, so this skips the second the webhook arrived in
            $start = $lastWebhook->at->modify('+1 second');
            $end = $this->clock->now()->sub(new \DateInterval(self::GRACE_PERIOD));
            if ($end < $start) {
                return null;
            }

            $events = $this->webhookEventGateway->getEvents(
                $this->apiContextFactory->getApiContext($salesChannelId),
                $start,
                $end,
                self::PAGE_SIZE,
            )->getEvents();
        } catch (\Throwable $e) {
            $this->logger->warning('Could not fetch the webhook events from PayPal, the diagnostics don\'t check for missed webhook events', Log::context(
                code: LogCode::DIAGNOSTICS_WEBHOOK_EVENTS_UNAVAILABLE,
                exception: $e,
                salesChannelId: $salesChannelId,
            ));

            return null;
        }

        // PayPal lists the newest event first, and the last webhook's own event is no missed one
        $missed = \array_values(\array_filter(
            \iterator_to_array($events),
            static fn (Event $event) => !$event->isset('id') || $event->getId() !== $lastWebhook->eventId,
        ));
        if ($missed === []) {
            return null;
        }

        return new MissedEvents(
            \count($missed),
            \count($events) >= self::PAGE_SIZE,
            $missed[0]->isset('createTime') ? $missed[0]->getCreateTime() : '',
            $missed[0]->isset('eventType') ? $missed[0]->getEventType() : '',
        );
    }
}
