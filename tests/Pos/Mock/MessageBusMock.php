<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Pos\Mock;

use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Swag\PayPal\Test\Pos\Mock\Repositories\MessageQueueStatsRepoMock;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageBusMock implements MessageBusInterface
{
    private MessageQueueStatsRepoMock $messageQueueStatsRepository;

    /**
     * @var Envelope[]
     */
    private array $envelopes = [];

    public function __construct()
    {
        $this->messageQueueStatsRepository = new MessageQueueStatsRepoMock();
    }

    public function dispatch($message, array $stamps = []): Envelope
    {
        $envelope = $message instanceof Envelope ? $message : new Envelope($message);
        $this->envelopes[] = $envelope;

        $this->messageQueueStatsRepository->modifyMessageStat(\get_class($envelope->getMessage()), 1);

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
     * @param AbstractMessageHandler[] $handlers
     */
    public function execute(array $handlers, bool $loop = true): void
    {
        do {
            $processed = [];
            foreach ($this->envelopes as $envelopeKey => $envelope) {
                foreach ($handlers as $handler) {
                    foreach ($handler::getHandledMessages() as $messageType) {
                        if (\get_class($envelope->getMessage()) === $messageType) {
                            $handler->handle($envelope->getMessage());
                            $this->messageQueueStatsRepository->modifyMessageStat(\get_class($envelope->getMessage()), -1);
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

    public function getMessageQueueStatsRepository(): MessageQueueStatsRepoMock
    {
        return $this->messageQueueStatsRepository;
    }
}
