<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;

#[Package('checkout')]
class RunService
{
    private EntityRepository $runRepository;

    private EntityRepository $logRepository;

    private Connection $connection;

    private Logger $logger;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $runRepository,
        EntityRepository $logRepository,
        Connection $connection,
        Logger $logger,
    ) {
        $this->runRepository = $runRepository;
        $this->logRepository = $logRepository;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function startRun(string $salesChannelId, string $taskName, array $steps, Context $context): string
    {
        $runId = Uuid::randomHex();

        $this->runRepository->create([[
            'id' => $runId,
            'salesChannelId' => $salesChannelId,
            'task' => $taskName,
            'steps' => $steps,
        ]], $context);

        return $runId;
    }

    public function writeLog(string $runId, Context $context): void
    {
        $logHandler = $this->getLogHandler();

        if ($logHandler === null) {
            return;
        }

        $logs = $logHandler->getLogs();

        if (\count($logs) === 0) {
            return;
        }

        foreach ($logs as &$log) {
            $log['runId'] = $runId;
        }
        unset($log);

        $this->logRepository->create($logs, $context);

        $logHandler->flush();
    }

    public function finishRun(string $runId, Context $context, string $status = PosSalesChannelRunDefinition::STATUS_FINISHED): void
    {
        $data = [
            'id' => $runId,
            'finishedAt' => new \DateTime(),
            'status' => $status,
            'messageCount' => 0,
        ];

        $this->runRepository->update([$data], $context);
    }

    public function abortRun(string $runId, Context $context): void
    {
        $this->logger->emergency('This sync has been aborted.');
        $this->writeLog($runId, $context);
        $this->finishRun($runId, $context, PosSalesChannelRunDefinition::STATUS_CANCELLED);
    }

    public function isRunActive(string $runId, Context $context): bool
    {
        /** @var PosSalesChannelRunEntity|null $run */
        $run = $this->runRepository->search(new Criteria([$runId]), $context)->first();

        if ($run === null) {
            return false;
        }

        return $run->getStatus() === PosSalesChannelRunDefinition::STATUS_IN_PROGRESS;
    }

    public function getActualMessageCount(Context $context): int
    {
        $criteria = new Criteria();
        $criteria->addAggregation(new SumAggregation('totalMessages', 'messageCount'));
        $criteria->addFilter(new EqualsFilter('status', PosSalesChannelRunDefinition::STATUS_IN_PROGRESS));

        /** @var SumResult|null $queued */
        $queued = $this->runRepository->aggregate($criteria, $context)->get('totalMessages');

        if ($queued === null || $queued->getSum() <= 0) {
            return 0;
        }

        return (int) $queued->getSum();
    }

    public function increaseStep(string $runId, int $currentStep, Context $context): void
    {
        $this->runRepository->update([[
            'id' => $runId,
            'stepIndex' => $currentStep + 1,
            'messageCount' => 0,
        ]], $context);
    }

    public function decrementMessageCount(string $runId): void
    {
        $this->connection->executeStatement(
            'UPDATE `swag_paypal_pos_sales_channel_run`
                    SET
                        `message_count` = GREATEST(0, `message_count` - 1),
                        `updated_at` = :updatedAt
                    WHERE `id` = :runId',
            ['runId' => Uuid::fromHexToBytes($runId), 'updatedAt' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
        );
    }

    public function getRun(string $runId, Context $context): PosSalesChannelRunEntity
    {
        /** @var PosSalesChannelRunEntity|null $run */
        $run = $this->runRepository->search(new Criteria([$runId]), $context)->first();

        if ($run === null) {
            throw new \RuntimeException('Run not found');
        }

        return $run;
    }

    private function getLogHandler(): ?LogHandler
    {
        foreach ($this->logger->getHandlers() as $handler) {
            if ($handler instanceof LogHandler) {
                return $handler;
            }
        }

        return null;
    }
}
