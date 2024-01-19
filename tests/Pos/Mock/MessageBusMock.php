<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Envelope;
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
     * @param array<object&callable> $handlers
     */
    public function execute(array $handlers, bool $loop = true): void
    {
        $messageTypes = $this->getMessageTypes($handlers);

        do {
            $processed = [];
            foreach ($this->envelopes as $envelopeKey => $envelope) {
                foreach ($handlers as $handler) {
                    if (\get_class($envelope->getMessage()) === $messageTypes[$handler::class]) {
                        $handler($envelope->getMessage());
                        $processed[] = $envelopeKey;
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

    /**
     * @param array<object&callable> $handlers
     *
     * @return array<class-string, string>
     */
    private function getMessageTypes(array $handlers): array
    {
        $handlersWithMessages = [];

        foreach ($handlers as $handler) {
            $class = $this->findMessageTypeByAttribute($handler::class) ?? $this->findMessageTypeByParameter($handler::class);

            if (!$class) {
                throw new \RuntimeException('Could not find message type');
            }

            $handlersWithMessages[$handler::class] = $class;
        }

        return $handlersWithMessages;
    }

    /**
     * @param class-string $handler
     */
    private function findMessageTypeByAttribute(string $handler): ?string
    {
        $reflection = new \ReflectionClass($handler);
        $attributes = $reflection->getAttributes();

        foreach ($attributes as $attribute) {
            if ($attribute->getName() === AsMessageHandler::class) {
                return $attribute->getArguments()['handles'] ?? null;
            }
        }

        return null;
    }

    /**
     * @param class-string $handler
     */
    private function findMessageTypeByParameter(string $handler): ?string
    {
        $reflection = new \ReflectionMethod($handler, '__invoke');
        $parameters = $reflection->getParameters();
        $messageType = $parameters[0]->getType();

        if ($messageType instanceof \ReflectionNamedType) {
            return $messageType->getName();
        }

        return null;
    }
}
