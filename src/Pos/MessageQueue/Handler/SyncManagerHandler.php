<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelRunDefinition;
use Swag\PayPal\Pos\Exception\UnknownSyncStepException;
use Swag\PayPal\Pos\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Manager\ProductSyncManager;
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
class SyncManagerHandler
{
    public const SYNC_PRODUCT = 'product';
    public const SYNC_IMAGE = 'image';
    public const SYNC_INVENTORY = 'inventory';
    public const SYNC_CLONE_VISIBILITY = 'cloneVisibility';

    private MessageDispatcher $messageBus;

    private RunService $runService;

    private LoggerInterface $logger;

    private ImageSyncManager $imageSyncManager;

    private InventorySyncManager $inventorySyncManager;

    private ProductSyncManager $productSyncManager;

    private MessageHydrator $messageHydrator;

    public function __construct(
        MessageDispatcher $messageBus,
        MessageHydrator $messageHydrator,
        RunService $runService,
        LoggerInterface $logger,
        ImageSyncManager $imageSyncManager,
        InventorySyncManager $inventorySyncManager,
        ProductSyncManager $productSyncManager,
    ) {
        $this->messageBus = $messageBus;
        $this->runService = $runService;
        $this->logger = $logger;
        $this->imageSyncManager = $imageSyncManager;
        $this->inventorySyncManager = $inventorySyncManager;
        $this->productSyncManager = $productSyncManager;
        $this->messageHydrator = $messageHydrator;
    }

    public function __invoke(SyncManagerMessage $message): void
    {
        $runId = $message->getRunId();
        $context = $message->getContext();

        if (!$this->runService->isRunActive($runId, $context)) {
            return;
        }

        try {
            $this->messageHydrator->hydrateMessage($message);

            if (!$this->isReadyForNextStep($message)) {
                return;
            }

            $steps = $message->getSteps();
            $currentStep = $message->getCurrentStep();

            if ($currentStep >= \count($steps)) {
                $this->runService->finishRun($runId, $context);

                return;
            }

            $messages = $this->createMessages(
                $steps[$currentStep],
                $runId,
                $message->getSalesChannel(),
                $context
            );

            if (empty($messages)) {
                $this->runService->increaseStep($runId, $currentStep, $context);
                $message->setCurrentStep($currentStep + 1);
                $this->messageBus->dispatch($message);

                return;
            }

            $this->messageBus->bulkDispatch($messages, $runId);
        } catch (\Throwable $e) {
            $this->logger->critical($e->__toString());
            $this->runService->finishRun($runId, $context, PosSalesChannelRunDefinition::STATUS_FAILED);
        } finally {
            $this->runService->writeLog($runId, $context);
        }
    }

    /**
     * @return AbstractSyncMessage[]
     */
    private function createMessages(string $step, string $runId, SalesChannelEntity $salesChannel, Context $context): array
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
        return $this->runService->getActualMessageCount($message->getContext()) <= 0;
    }
}
