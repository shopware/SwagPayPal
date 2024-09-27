<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler\Sync;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\MessageQueue\MessageHydrator;
use Swag\PayPal\Pos\Run\RunService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler]
abstract class AbstractSyncHandler
{
    private RunService $runService;

    private LoggerInterface $logger;

    private MessageDispatcher $messageBus;

    private MessageHydrator $messageHydrator;

    public function __construct(
        RunService $runService,
        LoggerInterface $logger,
        MessageDispatcher $messageBus,
        MessageHydrator $messageHydrator,
    ) {
        $this->runService = $runService;
        $this->logger = $logger;
        $this->messageBus = $messageBus;
        $this->messageHydrator = $messageHydrator;
    }

    public function __invoke(AbstractSyncMessage $message): void
    {
        $runId = $message->getRunId();
        $context = $message->getContext();

        if (!$this->runService->isRunActive($runId, $context)) {
            return;
        }

        try {
            $this->messageHydrator->hydrateMessage($message);
            $this->sync($message);
        } catch (\Throwable $e) {
            $this->logger->critical($e->__toString());
            $this->runService->finishRun($runId, $context, PosSalesChannelRunDefinition::STATUS_CANCELLED);
        } finally {
            $this->runService->decrementMessageCount($runId);
            $this->checkRunStep($message);
            $this->runService->writeLog($runId, $context);
        }
    }

    abstract protected function sync(AbstractSyncMessage $message): void;

    private function checkRunStep(AbstractSyncMessage $message): void
    {
        $run = $this->runService->getRun($message->getRunId(), $message->getContext());

        if ($run->getMessageCount() !== 0) {
            return;
        }

        $this->runService->increaseStep($message->getRunId(), $run->getStepIndex(), $message->getContext());
        $managerMessage = new SyncManagerMessage();
        $managerMessage->setSalesChannel($message->getSalesChannel());
        $managerMessage->setRunId($message->getRunId());
        $managerMessage->setSteps($run->getSteps());
        $managerMessage->setCurrentStep($run->getStepIndex() + 1);

        $this->messageBus->dispatch($managerMessage);
    }
}
