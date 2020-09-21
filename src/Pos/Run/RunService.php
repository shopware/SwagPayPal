<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run;

use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunEntity;

class RunService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $runRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $logRepository;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(
        EntityRepositoryInterface $runRepository,
        EntityRepositoryInterface $logRepository,
        Logger $logger
    ) {
        $this->runRepository = $runRepository;
        $this->logRepository = $logRepository;
        $this->logger = $logger;
    }

    public function startRun(string $salesChannelId, string $taskName, Context $context): string
    {
        $runId = Uuid::randomHex();

        $this->runRepository->create([[
            'id' => $runId,
            'salesChannelId' => $salesChannelId,
            'task' => $taskName,
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

    public function finishRun(string $runId, Context $context, bool $abortedByUser = false): void
    {
        $data = [
            'id' => $runId,
            'finishedAt' => new \DateTime(),
        ];

        if ($abortedByUser) {
            $data['abortedByUser'] = true;
        }

        $this->runRepository->update([$data], $context);
    }

    public function abortRun(string $runId, Context $context): void
    {
        $this->logger->emergency('This sync has been aborted.');
        $this->writeLog($runId, $context);
        $this->finishRun($runId, $context, true);
    }

    public function isRunActive(string $runId, Context $context): bool
    {
        /** @var PosSalesChannelRunEntity|null $run */
        $run = $context->disableCache(function (Context $context) use ($runId) {
            return $this->runRepository->search(new Criteria([$runId]), $context);
        })->first();

        if ($run === null) {
            return false;
        }

        return $run->getFinishedAt() === null;
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
