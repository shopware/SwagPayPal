<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Task;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\RunService;

#[Package('checkout')]
abstract class AbstractTask
{
    private MessageDispatcher $messageBus;

    private RunService $runService;

    /**
     * @internal
     */
    public function __construct(
        MessageDispatcher $messageBus,
        RunService $runService,
    ) {
        $this->messageBus = $messageBus;
        $this->runService = $runService;
    }

    public function execute(SalesChannelEntity $salesChannel, Context $context): string
    {
        $runId = $this->runService->startRun($salesChannel->getId(), $this->getRunTaskName(), $this->getSteps(), $context);

        $message = new SyncManagerMessage();
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
