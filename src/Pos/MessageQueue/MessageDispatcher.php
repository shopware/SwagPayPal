<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Symfony\Component\Messenger\MessageBusInterface;

#[Package('checkout')]
class MessageDispatcher
{
    protected MessageBusInterface $messageBus;

    protected Connection $connection;

    /**
     * @internal
     */
    public function __construct(
        MessageBusInterface $messageBus,
        Connection $connection,
    ) {
        $this->messageBus = $messageBus;
        $this->connection = $connection;
    }

    public function dispatch(AbstractSyncMessage $message, bool $tracked = false): void
    {
        if ($tracked) {
            $this->incrementMessageCount($message->getRunId(), 1);
        }

        $this->messageBus->dispatch($message);
    }

    public function bulkDispatch(array $messages, string $runId): void
    {
        $this->incrementMessageCount($runId, \count($messages));

        foreach ($messages as $message) {
            $this->messageBus->dispatch($message);
        }
    }

    private function incrementMessageCount(string $runId, int $amount): void
    {
        $this->connection->executeStatement(
            'UPDATE `swag_paypal_pos_sales_channel_run`
            SET
                `message_count` = `message_count` + :amount,
                `updated_at` = :updatedAt
            WHERE `id` = :runId',
            [
                'runId' => Uuid::fromHexToBytes($runId),
                'amount' => $amount,
                'updatedAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }
}
