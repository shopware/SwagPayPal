<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\MessageQueue\Handler;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\Exception\UnknownSyncStepException;
use Swag\PayPal\IZettle\MessageQueue\Manager\ImageSyncManager;
use Swag\PayPal\IZettle\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\IZettle\MessageQueue\Manager\ProductSyncManager;
use Swag\PayPal\IZettle\MessageQueue\Message\CloneVisibilityMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ImageSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductSingleSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductVariantSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\IZettle\Run\RunService;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class SyncManagerHandler extends AbstractMessageHandler
{
    public const SYNC_PRODUCT = 'product';
    public const SYNC_IMAGE = 'image';
    public const SYNC_INVENTORY = 'inventory';
    private const QUEUED_DELAY = 2000;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var EntityRepositoryInterface
     */
    private $messageQueueStatsRepository;

    /**
     * @var RunService
     */
    private $runService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ImageSyncManager
     */
    private $imageSyncManager;

    /**
     * @var InventorySyncManager
     */
    private $inventorySyncManager;

    /**
     * @var ProductSyncManager
     */
    private $productSyncManager;

    public function __construct(
        MessageBusInterface $messageBus,
        EntityRepositoryInterface $messageQueueStatsRepository,
        RunService $runService,
        LoggerInterface $logger,
        ImageSyncManager $imageSyncManager,
        InventorySyncManager $inventorySyncManager,
        ProductSyncManager $productSyncManager
    ) {
        $this->messageBus = $messageBus;
        $this->messageQueueStatsRepository = $messageQueueStatsRepository;
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
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', [
            CloneVisibilityMessage::class,
            ImageSyncMessage::class,
            InventorySyncMessage::class,
            ProductSingleSyncMessage::class,
            ProductVariantSyncMessage::class,
            ProductCleanupSyncMessage::class,
        ]));
        $criteria->addAggregation(new SumAggregation('totalSize', 'size'));

        /** @var SumResult|null $queued */
        $queued = $message->getContext()->disableCache(function (Context $context) use ($criteria) {
            return $this->messageQueueStatsRepository->aggregate($criteria, $context)->get('totalSize');
        });

        if ($queued !== null && $queued->getSum() > 0) {
            $envelope = new Envelope($message, [
                new DelayStamp(self::QUEUED_DELAY),
            ]);
            $this->messageBus->dispatch($envelope);

            return;
        }

        try {
            $steps = $message->getSteps();

            if ($message->getCurrentStep() >= \count($steps)) {
                $this->runService->finishRun($message->getRunId(), $message->getContext());

                return;
            }

            $this->buildMessages(
                $steps[$message->getCurrentStep()],
                $message->getRunId(),
                $message->getSalesChannel(),
                $message->getContext()
            );

            $message->setCurrentStep($message->getCurrentStep() + 1);

            $this->messageBus->dispatch($message);
        } catch (\Throwable $e) {
            $this->logger->critical($e->__toString());
        } finally {
            $this->runService->writeLog($message->getRunId(), $message->getContext());
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [
            SyncManagerMessage::class,
        ];
    }

    private function buildMessages(string $step, string $runId, SalesChannelEntity $salesChannel, Context $context): void
    {
        switch ($step) {
            case self::SYNC_IMAGE:
                $this->imageSyncManager->buildMessages($salesChannel, $context, $runId);

                return;
            case self::SYNC_INVENTORY:
                $this->inventorySyncManager->buildMessages($salesChannel, $context, $runId);

                return;
            case self::SYNC_PRODUCT:
                $this->productSyncManager->buildMessages($salesChannel, $context, $runId);

                return;
            default:
                throw new UnknownSyncStepException($step);
        }
    }
}
