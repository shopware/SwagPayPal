<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook\Recording;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * Keyed by the execute token, as Sales Channels without own webhook settings use the inherited one.
 *
 * @internal
 */
#[Package('checkout')]
class LastWebhookStorage
{
    public const KEY_PREFIX = 'swag_paypal.webhook.';

    public function __construct(
        private readonly AbstractKeyValueStorage $keyValueStorage,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
        private readonly TestWebhookStorage $testWebhookStorage,
    ) {
    }

    /**
     * Never throws, recording must not break the answer to PayPal
     */
    public function record(string $executeToken, string $eventType, WebhookOutcome $outcome, string $eventId = ''): void
    {
        try {
            $now = $this->clock->now();
            $this->keyValueStorage->set($this->getKey($executeToken), [
                'at' => $now->format(\DateTimeInterface::ATOM),
                'eventType' => $eventType,
                'outcome' => $outcome->value,
                'eventId' => $eventId,
            ]);
            $this->testWebhookStorage->recordArrival($executeToken, $eventId, $now);
        } catch (\Throwable $e) {
            $this->logger->warning('Could not record the webhook event {webhookEventType}, the diagnostics don\'t show it', Log::context(
                code: LogCode::DIAGNOSTICS_WEBHOOK_RECORDING_FAILED,
                exception: $e,
                webhookEventId: $eventId,
                webhookEventType: $eventType,
            ));
        }
    }

    public function get(string $executeToken): ?LastWebhook
    {
        $value = $this->keyValueStorage->get($this->getKey($executeToken));

        // The MySQL storage returns arrays JSON encoded
        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        if (!\is_array($value) || !\is_string($value['at'] ?? null) || !\is_string($value['eventType'] ?? null) || !\is_string($value['outcome'] ?? null) || !\is_string($value['eventId'] ?? null)) {
            return null;
        }

        $at = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value['at']);
        $outcome = WebhookOutcome::tryFrom($value['outcome']);
        if ($at === false || $outcome === null) {
            return null;
        }

        return new LastWebhook($at, $value['eventType'], $outcome, $value['eventId']);
    }

    /**
     * The token authorizes webhooks, so it isn't stored
     */
    private function getKey(string $executeToken): string
    {
        return self::KEY_PREFIX . Hasher::hash($executeToken);
    }
}
