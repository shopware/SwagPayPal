<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Handler;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Swag\PayPal\Pos\MessageQueue\Manager\InventorySyncManager;
use Swag\PayPal\Pos\MessageQueue\Message\InventoryUpdateMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\Pos\Run\Task\InventoryTask;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[Package('checkout')]
#[AsMessageHandler]
class InventoryUpdateHandler
{
    private RunService $runService;

    private EntityRepository $salesChannelRepository;

    private InventorySyncManager $inventorySyncManager;

    private MessageDispatcher $messageBus;

    public function __construct(
        RunService $runService,
        EntityRepository $salesChannelRepository,
        InventorySyncManager $inventorySyncManager,
        MessageDispatcher $messageBus,
    ) {
        $this->runService = $runService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->inventorySyncManager = $inventorySyncManager;
        $this->messageBus = $messageBus;
    }

    public function __invoke(InventoryUpdateMessage $message): void
    {
        $context = $message->getContext();

        foreach ($this->getSalesChannels($context) as $salesChannel) {
            $runId = $this->runService->startRun(
                $salesChannel->getId(),
                InventoryTask::TASK_NAME_INVENTORY,
                [SyncManagerHandler::SYNC_INVENTORY],
                $context
            );

            $messages = $this->inventorySyncManager->createMessages($salesChannel, $context, $runId, $message->getIds());

            $this->messageBus->bulkDispatch($messages, $runId);
        }
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
