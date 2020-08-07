<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\MessageQueue\Manager;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\Bucket;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\InventorySyncMessage;
use Swag\PayPal\IZettle\Sync\Context\InventoryContext;
use Swag\PayPal\IZettle\Sync\Context\InventoryContextFactory;
use Swag\PayPal\IZettle\Sync\ProductSelection;
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

    public function buildMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): void
    {
        $salesChannelContext = $this->productSelection->getSalesChannelContext($salesChannel);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $productStreamId = $iZettleSalesChannel->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $context);
        $parentIds = $this->getParentIds(clone $criteria, $salesChannelContext);
        $productIds = $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
        $inventoryContext = $this->inventoryContextFactory->getContext($salesChannel, $context);

        $accumulatedIds = [];
        foreach ($productIds as $id) {
            $accumulatedIds[] = $id;

            if (\count($accumulatedIds) >= self::CHUNK_SIZE) {
                $this->createMessage($context, $inventoryContext, $salesChannel, $runId, $accumulatedIds, $parentIds);
                $accumulatedIds = [];
            }
        }

        if (\count($accumulatedIds) > 0) {
            $this->createMessage($context, $inventoryContext, $salesChannel, $runId, $accumulatedIds, $parentIds);
        }
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
        $criteria->addAggregation(new TermsAggregation('parentIds', 'parentId'));
        $criteria->addFilter(new NotFilter(NotFilter::CONNECTION_OR, [
            new EqualsFilter('parentId', null),
        ]));

        /** @var BucketResult|null $aggregate */
        $aggregate = $this->productRepository->aggregate($criteria, $salesChannelContext)->get('parentIds');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product count');
        }
        $buckets = $aggregate->getBuckets();

        return \array_map(static function (Bucket $bucket) {
            return $bucket->getKey();
        }, $buckets);
    }
}
