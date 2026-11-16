<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Webhook\Recording;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Hasher;
use Swag\PayPal\Diagnostics\Webhook\TestWebhook;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * Apart from the last webhook, which any webhook arriving in between replaces.
 *
 * @internal
 */
#[Package('checkout')]
class TestWebhookStorage
{
    public const KEY_PREFIX = 'swag_paypal.webhook_test.';

    private const PENDING = 'pending.';
    private const ARRIVAL = 'arrival.';

    public function __construct(
        private readonly AbstractKeyValueStorage $keyValueStorage,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function expect(string $executeToken, TestWebhook $test): void
    {
        $this->keyValueStorage->set($this->getKey(self::PENDING, $executeToken), [
            'eventId' => $test->eventId,
            'sentAt' => $test->sentAt->format(\DateTimeInterface::ATOM),
        ]);
        $this->keyValueStorage->remove($this->getKey(self::ARRIVAL, $executeToken));
    }

    /**
     * Never throws, recording must not break the answer to PayPal
     */
    public function recordArrival(string $executeToken, string $eventId, \DateTimeImmutable $at): void
    {
        try {
            if ($this->getPending($executeToken)?->eventId === $eventId) {
                $this->keyValueStorage->set($this->getKey(self::ARRIVAL, $executeToken), ['eventId' => $eventId, 'at' => $at->format(\DateTimeInterface::ATOM)]);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Could not record the arrival of the test webhook event {webhookEventId}, the diagnostics show it as missing', Log::context(
                code: LogCode::DIAGNOSTICS_WEBHOOK_TEST_RECORDING_FAILED,
                exception: $e,
                webhookEventId: $eventId,
            ));
        }
    }

    /**
     * Test events share one ID, so only an arrival since sending counts.
     */
    public function hasArrived(string $executeToken): bool
    {
        $pending = $this->getPending($executeToken);
        $arrival = $this->read($this->getKey(self::ARRIVAL, $executeToken));
        $at = \is_string($arrival['at'] ?? null) ? \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $arrival['at']) : false;

        return $pending !== null && $at !== false && ($arrival['eventId'] ?? null) === $pending->eventId && $at >= $pending->sentAt;
    }

    private function getPending(string $executeToken): ?TestWebhook
    {
        $pending = $this->read($this->getKey(self::PENDING, $executeToken));
        $sentAt = \is_string($pending['sentAt'] ?? null) ? \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $pending['sentAt']) : false;

        return \is_string($pending['eventId'] ?? null) && $sentAt !== false ? new TestWebhook($pending['eventId'], $sentAt) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function read(string $key): array
    {
        $value = $this->keyValueStorage->get($key);
        // The MySQL storage returns arrays JSON encoded
        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        return \is_array($value) ? $value : [];
    }

    /**
     * The token authorizes webhooks, so it isn't stored
     */
    private function getKey(string $kind, string $executeToken): string
    {
        return self::KEY_PREFIX . $kind . Hasher::hash($executeToken);
    }
}
