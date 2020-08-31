<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Task;

use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\Run\RunService;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class AbstractTask
{
    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var RunService
     */
    private $runService;

    public function __construct(
        MessageBusInterface $messageBus,
        RunService $runService
    ) {
        $this->messageBus = $messageBus;
        $this->runService = $runService;
    }

    public function execute(SalesChannelEntity $salesChannel, Context $context): string
    {
        $runId = $this->runService->startRun($salesChannel->getId(), $this->getRunTaskName(), $context);

        $message = new SyncManagerMessage();
        $message->setContext($context);
        $message->setSalesChannel($salesChannel);
        $message->setRunId($runId);
        $message->setSteps($this->getSteps());
        $message->setCurrentStep(0);
        $this->messageBus->dispatch($message);
        $this->runService->writeLog($runId, $context);

        return $runId;
    }

    abstract public function getRunTaskName(): string;

    abstract public function getSteps(): array;
}
