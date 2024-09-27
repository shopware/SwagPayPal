<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Manager;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class InventorySyncManager extends AbstractSyncManager
{
    public const CHUNK_SIZE = 500;

    /**
     * @internal
     */
    public function __construct(
        MessageDispatcher $messageBus,
        private readonly ProductSelection $productSelection,
        private readonly SalesChannelRepository $productRepository,
        private readonly InventoryContextFactory $inventoryContextFactory,
        private readonly bool $stockManagementEnabled,
    ) {
        parent::__construct($messageBus);
    }

    /**
     * @return AbstractSyncMessage[]
     */
    public function createMessages(SalesChannelEntity $salesChannel, Context $context, string $runId, ?array $reducedIds = null): array
    {
        if (!$this->stockManagementEnabled) {
            return [];
        }

        $salesChannelContext = $this->productSelection->getSalesChannelContext($salesChannel);

        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        $productStreamId = $posSalesChannel->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $context);
        $parentIds = $this->getParentIds(clone $criteria, $salesChannelContext);
        if ($reducedIds !== null) {
            $criteria->setIds($reducedIds);
        }

        $productIds = $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
        if (empty($productIds)) {
            return [];
        }

        $inventoryContext = $this->inventoryContextFactory->getContext($salesChannel);

        $accumulatedIds = [];
        $messages = [];

        foreach ($productIds as $id) {
            $accumulatedIds[] = $id;

            if (\count($accumulatedIds) >= self::CHUNK_SIZE) {
                $messages[] = $this->createMessage($context, $inventoryContext, $salesChannel, $runId, $accumulatedIds, $parentIds);
                $accumulatedIds = [];
            }
        }

        if (\count($accumulatedIds) > 0) {
            $messages[] = $this->createMessage($context, $inventoryContext, $salesChannel, $runId, $accumulatedIds, $parentIds);
        }

        return $messages;
    }

    private function createMessage(
        Context $context,
        InventoryContext $inventoryContext,
        SalesChannelEntity $salesChannel,
        string $runId,
        array $accumulatedIds,
        array $parentIds,
    ): InventorySyncMessage {
        $message = new InventorySyncMessage();
        $message->setInventoryContext($this->inventoryContextFactory->filterContext($inventoryContext, $accumulatedIds, $parentIds));
        $message->setRunId($runId);
        $message->setSalesChannel($salesChannel);

        return $message;
    }

    private function getParentIds(Criteria $criteria, SalesChannelContext $salesChannelContext): array
    {
        $criteria->addFilter(new RangeFilter('childCount', [RangeFilter::GT => 0]));

        return $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }
}
