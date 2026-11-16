<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\ExpressCallback\Recording;

use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Adapter\Storage\AbstractKeyValueStorage;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
class LastCallbackStorage
{
    public const KEY_PREFIX = 'swag_paypal.express_callback.';

    public function __construct(
        private readonly AbstractKeyValueStorage $keyValueStorage,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Never throws, recording must not break the callback
     */
    public function record(string $salesChannelId, CallbackOutcome $outcome): void
    {
        try {
            $this->keyValueStorage->set(self::KEY_PREFIX . $salesChannelId, [
                'at' => $this->clock->now()->format(\DateTimeInterface::ATOM),
                'outcome' => $outcome->value,
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Could not record the shipping callback, the diagnostics don\'t show it', Log::context(
                code: LogCode::DIAGNOSTICS_CALLBACK_RECORDING_FAILED,
                exception: $e,
                salesChannelId: $salesChannelId,
            ));
        }
    }

    public function get(string $salesChannelId): ?LastCallback
    {
        $value = $this->keyValueStorage->get(self::KEY_PREFIX . $salesChannelId);

        // The MySQL storage returns arrays JSON encoded
        if (\is_string($value)) {
            $value = \json_decode($value, true);
        }

        if (!\is_array($value) || !\is_string($value['at'] ?? null) || !\is_string($value['outcome'] ?? null)) {
            return null;
        }

        $at = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $value['at']);
        $outcome = CallbackOutcome::tryFrom($value['outcome']);
        if ($at === false || $outcome === null) {
            return null;
        }

        return new LastCallback($at, $outcome);
    }
}
