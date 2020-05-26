<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run;

use Monolog\Logger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelRunEntity;

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

    public function startRun(string $salesChannelId, Context $context): IZettleSalesChannelRunEntity
    {
        $run = new IZettleSalesChannelRunEntity();
        $run->setId(Uuid::randomHex());
        $run->setSalesChannelId($salesChannelId);

        $this->runRepository->create([[
            'id' => $run->getId(),
            'salesChannelId' => $salesChannelId,
        ]], $context);

        return $run;
    }

    public function finishRun(IZettleSalesChannelRunEntity $run, Context $context): void
    {
        $logHandler = $this->getLogHandler();
        if ($logHandler === null) {
            return;
        }
        $logs = $logHandler->getLogs();

        if (\count($logs) > 0) {
            foreach ($logs as &$log) {
                $log['runId'] = $run->getId();
            }
            unset($log);

            $this->logRepository->create($logs, $context);
        }

        $this->runRepository->update([[
            'id' => $run->getId(),
        ]], $context);

        $logHandler->flush();
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
