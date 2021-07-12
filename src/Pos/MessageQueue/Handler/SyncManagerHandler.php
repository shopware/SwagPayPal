<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\Exception\MessageQueueTimeoutException;
use Swag\PayPal\Pos\Exception\UnknownSyncStepException;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\Run\RunService;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class SyncManagerHandler extends AbstractMessageHandler
{
    public const SYNC_PRODUCT = 'product';
    public const SYNC_IMAGE = 'image';
    public const SYNC_INVENTORY = 'inventory';
    public const SYNC_CLONE_VISIBILITY = 'cloneVisibility';
    private const QUEUED_DELAY = 2000;
    private const QUEUE_RETRIES = 5; // about 2 minutes

    private MessageBusInterface $messageBus;

    private RunService $runService;

    private LoggerInterface $logger;

    private ImageSyncManager $imageSyncManager;

    private InventorySyncManager $inventorySyncManager;

    private ProductSyncManager $productSyncManager;

    public function __construct(
        MessageBusInterface $messageBus,
        RunService $runService,
        LoggerInterface $logger,
        ImageSyncManager $imageSyncManager,
        InventorySyncManager $inventorySyncManager,
        ProductSyncManager $productSyncManager
    ) {
        $this->messageBus = $messageBus;
        $this->runService = $runService;
        $this->logger = $logger;
        $this->imageSyncManager = $imageSyncManager;
        $this->inventorySyncManager = $inventorySyncManager;
        $this->productSyncManager = $productSyncManager;
    }

    /**
     * @param SyncManagerMessage $message
     */
    public function handle($message): void
    {
        $runId = $message->getRunId();
        $context = $message->getContext();

        if (!$this->runService->isRunActive($runId, $context)) {
            return;
        }

        try {
            if ($message->getMessageRetries() > self::QUEUE_RETRIES) {
                throw new MessageQueueTimeoutException();
            }

            if (!$this->isReadyForNextStep($message)) {
                return;
            }

            $steps = $message->getSteps();
            $currentStep = $message->getCurrentStep();

            if ($currentStep >= \count($steps)) {
                $this->runService->finishRun($runId, $context);

                return;
            }

            $messageCount = $this->createMessages(
                $steps[$currentStep],
                $runId,
                $message->getSalesChannel(),
                $context
            );

            $this->runService->setMessageCount($messageCount, $runId, $context);

            $message->setCurrentStep($currentStep + 1);
            $message->setLastMessageCount($messageCount);
            $message->setMessageRetries(0);

            $this->messageBus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->critical($e->__toString());
            $this->runService->finishRun($runId, $context, false, PosSalesChannelRunDefinition::STATUS_FAILED);
        } finally {
            $this->runService->writeLog($runId, $context);
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [
            SyncManagerMessage::class,
        ];
    }

    private function createMessages(string $step, string $runId, SalesChannelEntity $salesChannel, Context $context): int
    {
        switch ($step) {
            case self::SYNC_IMAGE:
                return $this->imageSyncManager->createMessages($salesChannel, $context, $runId);
            case self::SYNC_INVENTORY:
                return $this->inventorySyncManager->createMessages($salesChannel, $context, $runId);
            case self::SYNC_PRODUCT:
                return $this->productSyncManager->createMessages($salesChannel, $context, $runId);
            default:
                throw new UnknownSyncStepException($step);
        }
    }

    private function isReadyForNextStep(SyncManagerMessage $message): bool
    {
        $currentMessageCount = $this->runService->getActualMessageCount($message->getContext());

        if ($currentMessageCount <= 0) {
            return true;
        }

        if ($message->getLastMessageCount() === $currentMessageCount) {
            $message->setMessageRetries($message->getMessageRetries() + 1);
        } else {
            $message->setLastMessageCount($currentMessageCount);
        }

        $envelope = new Envelope($message, [
            new DelayStamp(self::QUEUED_DELAY * (2 ** $message->getMessageRetries())),
        ]);

        $this->messageBus->dispatch($envelope);

        return false;
    }
}
