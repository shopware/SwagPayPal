<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\MessageQueue\Manager;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Message\AbstractSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductSingleSyncMessage;
use Swag\PayPal\Pos\MessageQueue\Message\Sync\ProductVariantSyncMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Sync\ImageSyncer;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class ProductSyncManager extends AbstractSyncManager
{
    public const CHUNK_SIZE = 50;

    private ProductSelection $productSelection;

    private SalesChannelRepository $productRepository;

    private ImageSyncer $imageSyncer;

    /**
     * @internal
     */
    public function __construct(
        MessageDispatcher $messageBus,
        ProductSelection $productSelection,
        SalesChannelRepository $productRepository,
        ImageSyncer $imageSyncer,
    ) {
        parent::__construct($messageBus);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->imageSyncer = $imageSyncer;
    }

    /**
     * @return AbstractSyncMessage[]
     */
    public function createMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): array
    {
        $salesChannelContext = $this->productSelection->getSalesChannelContext($salesChannel);

        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        $productStreamId = $posSalesChannel->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $context);

        $this->imageSyncer->cleanUp($salesChannel->getId(), $context);

        return \array_merge(
            $this->buildSingleMessages(clone $criteria, $salesChannelContext, $salesChannel, $runId),
            $this->buildVariantMessages(clone $criteria, $salesChannelContext, $salesChannel, $runId),
            $this->buildCleanupMessage($salesChannelContext, $salesChannel, $runId)
        );
    }

    /**
     * @return AbstractSyncMessage[]
     */
    private function buildSingleMessages(
        Criteria $criteria,
        SalesChannelContext $salesChannelContext,
        SalesChannelEntity $salesChannel,
        string $runId,
    ): array {
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addFilter(new EqualsFilter('childCount', 0));

        /** @var CountResult|null $aggregate */
        $aggregate = $this->productRepository->aggregate($criteria, $salesChannelContext)->get('count');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product count');
        }

        $offset = 0;
        $messages = [];

        while ($offset < $aggregate->getCount()) {
            $message = new ProductSingleSyncMessage();
            $message->setSalesChannelContext($salesChannelContext);
            $message->setRunId($runId);
            $message->setLimit(self::CHUNK_SIZE);
            $message->setOffset($offset);
            $message->setSalesChannel($salesChannel);
            $messages[] = $message;

            $offset += self::CHUNK_SIZE;
        }

        return $messages;
    }

    /**
     * @return AbstractSyncMessage[]
     */
    private function buildVariantMessages(
        Criteria $criteria,
        SalesChannelContext $salesChannelContext,
        SalesChannelEntity $salesChannel,
        string $runId,
    ): array {
        $criteria->addAggregation(new TermsAggregation('ids', 'id', null, null, new SumAggregation('count', 'childCount')));
        $criteria->addFilter(new RangeFilter('childCount', [RangeFilter::GT => 0]));

        /** @var BucketResult|null $aggregate */
        $aggregate = $this->productRepository->aggregate($criteria, $salesChannelContext)->get('ids');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product count');
        }
        $buckets = $aggregate->getBuckets();

        $ids = [];
        $chunkSize = 0;
        $messages = [];
        foreach ($buckets as $bucket) {
            $ids[] = $bucket->getKey();

            /** @var SumResult|null $result */
            $result = $bucket->getResult();

            $chunkSize += $result !== null ? $result->getSum() : 2;

            if ($chunkSize >= self::CHUNK_SIZE) {
                $messages[] = $this->createVariantMessage($salesChannelContext, $runId, $salesChannel, $ids);
                $ids = [];
            }
        }

        if (\count($ids) > 0) {
            $messages[] = $this->createVariantMessage($salesChannelContext, $runId, $salesChannel, $ids);
        }

        return $messages;
    }

    /**
     * @return AbstractSyncMessage[]
     */
    private function buildCleanupMessage(
        SalesChannelContext $salesChannelContext,
        SalesChannelEntity $salesChannel,
        string $runId,
    ): array {
        $message = new ProductCleanupSyncMessage();
        $message->setSalesChannelContext($salesChannelContext);
        $message->setRunId($runId);
        $message->setSalesChannel($salesChannel);

        return [$message];
    }

    private function createVariantMessage(SalesChannelContext $salesChannelContext, string $runId, SalesChannelEntity $salesChannel, array $ids): ProductVariantSyncMessage
    {
        $message = new ProductVariantSyncMessage();
        $message->setSalesChannelContext($salesChannelContext);
        $message->setRunId($runId);
        $message->setSalesChannel($salesChannel);
        $message->setIds($ids);

        return $message;
    }
}
