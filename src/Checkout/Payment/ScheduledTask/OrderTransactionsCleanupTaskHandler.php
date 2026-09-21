<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment\ScheduledTask;

use Doctrine\DBAL\Connection;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler(handles: OrderTransactionsCleanupTask::class)]
class OrderTransactionsCleanupTaskHandler extends ScheduledTaskHandler
{
    private const RETENTION_INTERVAL = '14 days';

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->connection->executeStatement(
            'DELETE FROM `swag_paypal_order_transactions` WHERE `created_at` < :retentionDate',
            [
                'retentionDate' => $this->clock->now()
                    ->modify('-' . self::RETENTION_INTERVAL)
                    ->setTimezone(new \DateTimeZone('UTC'))
                    ->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ],
        );
    }
}
