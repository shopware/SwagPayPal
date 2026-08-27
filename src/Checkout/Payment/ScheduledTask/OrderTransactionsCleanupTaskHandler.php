<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\Payment\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[Package('checkout')]
#[AsMessageHandler(handles: OrderTransactionsCleanupTask::class)]
class OrderTransactionsCleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM `swag_paypal_order_transactions` WHERE `created_at` < :retentionDate',
            ['retentionDate' => $this->clock->now()->modify('-14 days')->setTimezone(new \DateTimeZone('UTC'))->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
        );
    }
}
