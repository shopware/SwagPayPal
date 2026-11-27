<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Log\Retention;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
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
#[AsMessageHandler(handles: LogCleanupTask::class)]
class LogCleanupTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        EntityRepository $scheduledTaskRepository,
        LoggerInterface $logger,
        private readonly Connection $connection,
        private readonly ClockInterface $clock,
        private readonly int $maxAgeInDays,
        private readonly int $maxLines,
        private readonly int $batchSize,
    ) {
        parent::__construct($scheduledTaskRepository, $logger);
    }

    public function run(): void
    {
        $this->deleteOlderThan($this->clock->now()->modify(\sprintf('-%d days', $this->maxAgeInDays)));

        $boundary = $this->connection->fetchOne(
            'SELECT `created_at` FROM `swag_paypal_log` ORDER BY `created_at` DESC LIMIT 1 OFFSET :maxLines',
            ['maxLines' => $this->maxLines],
            ['maxLines' => ParameterType::INTEGER],
        );

        if (\is_string($boundary)) {
            // includes the boundary line
            $this->deleteOlderThan((new \DateTimeImmutable($boundary, new \DateTimeZone('UTC')))->modify('+1 millisecond'));
        }
    }

    private function deleteOlderThan(\DateTimeInterface $before): void
    {
        $before = \DateTimeImmutable::createFromInterface($before)->setTimezone(new \DateTimeZone('UTC'));

        do {
            $deleted = (int) $this->connection->executeStatement(
                'DELETE FROM `swag_paypal_log` WHERE `created_at` < :before LIMIT :batchSize',
                [
                    'before' => $before->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    'batchSize' => $this->batchSize,
                ],
                ['batchSize' => ParameterType::INTEGER],
            );
        } while ($deleted > 0 && $deleted >= $this->batchSize);
    }
}
