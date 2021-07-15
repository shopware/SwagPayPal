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
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\Pos\Sync\Context\InventoryContext;
use Swag\PayPal\Pos\Sync\Context\InventoryContextFactory;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Messenger\MessageBusInterface;

class InventorySyncManager extends AbstractSyncManager
{
    public const CHUNK_SIZE = 500;

    /**
     * @var InventoryContextFactory
     */
    private $inventoryContextFactory;

    /**
     * @var ProductSelection
     */
    private $productSelection;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        ProductSelection $productSelection,
        SalesChannelRepositoryInterface $productRepository,
        InventoryContextFactory $inventoryContextFactory
    ) {
        parent::__construct($messageBus);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->inventoryContextFactory = $inventoryContextFactory;
    }

    public function createMessages(SalesChannelEntity $salesChannel, Context $context, string $runId, ?array $reducedIds = null): int
    {
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
            return 0;
        }

        $inventoryContext = $this->inventoryContextFactory->getContext($salesChannel, $context);

        $accumulatedIds = [];
        $messageCount = 0;

        foreach ($productIds as $id) {
            $accumulatedIds[] = $id;

            if (\count($accumulatedIds) >= self::CHUNK_SIZE) {
                $this->createMessage($context, $inventoryContext, $salesChannel, $runId, $accumulatedIds, $parentIds);
                ++$messageCount;
                $accumulatedIds = [];
            }
        }

        if (\count($accumulatedIds) > 0) {
            $this->createMessage($context, $inventoryContext, $salesChannel, $runId, $accumulatedIds, $parentIds);
            ++$messageCount;
        }

        return $messageCount;
    }

    private function createMessage(
        Context $context,
        InventoryContext $inventoryContext,
        SalesChannelEntity $salesChannel,
        string $runId,
        array $accumulatedIds,
        array $parentIds
    ): void {
        $message = new InventorySyncMessage();
        $message->setContext($context);
        $message->setInventoryContext($this->inventoryContextFactory->filterContext($inventoryContext, $accumulatedIds, $parentIds));
        $message->setRunId($runId);
        $message->setSalesChannel($salesChannel);
        $this->messageBus->dispatch($message);
    }

    private function getParentIds(Criteria $criteria, SalesChannelContext $salesChannelContext): array
    {
        $criteria->addFilter(new RangeFilter('childCount', [RangeFilter::GT => 0]));

        return $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }
}
