<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\MessageQueue\Handler\AbstractMessageHandler;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Swag\PayPal\Pos\MessageQueue\Message\SyncManagerMessage;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Messenger\MessageBusInterface;

class InventoryUpdateHandler extends AbstractMessageHandler
{
    private RunService $runService;

    private EntityRepositoryInterface $salesChannelRepository;

    private InventorySyncManager $inventorySyncManager;

    private MessageBusInterface $messageBus;

    public function __construct(
        RunService $runService,
        EntityRepositoryInterface $salesChannelRepository,
        InventorySyncManager $inventorySyncManager,
        MessageBusInterface $messageBus
    ) {
        $this->runService = $runService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->inventorySyncManager = $inventorySyncManager;
        $this->messageBus = $messageBus;
    }

    /**
     * @param InventoryUpdateMessage $message
     */
    public function handle($message): void
    {
        $context = $message->getContext();

        foreach ($this->getSalesChannels($context) as $salesChannel) {
            $runId = $this->runService->startRun($salesChannel->getId(), InventoryTask::TASK_NAME_INVENTORY, $context);

            $messageCount = $this->inventorySyncManager->createMessages($salesChannel, $context, $runId, $message->getIds());

            $this->runService->setMessageCount($messageCount, $runId, $context);

            $managerMessage = new SyncManagerMessage();
            $managerMessage->setContext($context);
            $managerMessage->setSalesChannel($salesChannel);
            $managerMessage->setRunId($runId);
            $managerMessage->setSteps([SyncManagerHandler::SYNC_INVENTORY]);
            $managerMessage->setCurrentStep(1);
            $this->messageBus->dispatch($managerMessage);
        }
    }

    public static function getHandledMessages(): iterable
    {
        return [
            InventoryUpdateMessage::class,
        ];
    }

    private function getSalesChannels(Context $context): SalesChannelCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('typeId', SwagPayPal::SALES_CHANNEL_TYPE_POS));
        $criteria->addFilter(new EqualsFilter('active', true));
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        /** @var SalesChannelCollection $salesChannels */
        $salesChannels = $this->salesChannelRepository->search($criteria, $context)->getEntities();

        return $salesChannels;
    }
}
