<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
#[Package('checkout')]
class MessageBusMock implements MessageBusInterface
{
    /**
     * @var Envelope[]
     */
    private array $envelopes = [];

    public function dispatch($message, array $stamps = []): Envelope
    {
        $envelope = $message instanceof Envelope ? $message : new Envelope($message);
        $this->envelopes[] = $envelope;

        return $envelope;
    }

    /**
     * @return Envelope[]
     */
    public function getEnvelopes(): array
    {
        return $this->envelopes;
    }

    /**
     * @param array<callable&MessageSubscriberInterface> $handlers
     */
    public function execute(array $handlers, bool $loop = true): void
    {
        do {
            $processed = [];
            foreach ($this->envelopes as $envelopeKey => $envelope) {
                foreach ($handlers as $handler) {
                    foreach ($handler::getHandledMessages() as $messageType) {
                        if (\get_class($envelope->getMessage()) === $messageType) {
                            $handler($envelope->getMessage());
                            $processed[] = $envelopeKey;
                        }
                    }
                }
            }
            foreach ($processed as $key) {
                unset($this->envelopes[$key]);
            }
        } while ($loop && \count($processed) > 0);
    }

    public function getTotalWaitingMessages(): int
    {
        return \count($this->envelopes);
    }

    public function clear(): void
    {
        $this->envelopes = [];
    }
}
