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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\SumAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\BucketResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\SumResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductCleanupSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductSingleSyncMessage;
use Swag\PayPal\IZettle\MessageQueue\Message\Sync\ProductVariantSyncMessage;
use Swag\PayPal\IZettle\Sync\ImageSyncer;
use Swag\PayPal\IZettle\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductSyncManager extends AbstractSyncManager
{
    public const CHUNK_SIZE = 50;

    /**
     * @var ProductSelection
     */
    private $productSelection;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ImageSyncer
     */
    private $imageSyncer;

    public function __construct(
        MessageBusInterface $messageBus,
        ProductSelection $productSelection,
        SalesChannelRepositoryInterface $productRepository,
        ImageSyncer $imageSyncer
    ) {
        parent::__construct($messageBus);
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->imageSyncer = $imageSyncer;
    }

    public function buildMessages(SalesChannelEntity $salesChannel, Context $context, string $runId): void
    {
        $salesChannelContext = $this->productSelection->getSalesChannelContext($salesChannel);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_IZETTLE_EXTENSION);

        $productStreamId = $iZettleSalesChannel->getProductStreamId();
        $criteria = $this->productSelection->getProductStreamCriteria($productStreamId, $context);

        $this->imageSyncer->cleanUp($salesChannel->getId(), $context);

        $this->buildSingleMessages(clone $criteria, $salesChannelContext, $salesChannel, $runId);
        $this->buildVariantMessages(clone $criteria, $salesChannelContext, $salesChannel, $runId);
        $this->buildCleanupMessage($salesChannelContext, $salesChannel, $runId);
    }

    private function buildSingleMessages(
        Criteria $criteria,
        SalesChannelContext $salesChannelContext,
        SalesChannelEntity $salesChannel,
        string $runId
    ): void {
        $criteria->addAggregation(new CountAggregation('count', 'id'));
        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addFilter(new EqualsFilter('childCount', 0));

        /** @var CountResult|null $aggregate */
        $aggregate = $this->productRepository->aggregate($criteria, $salesChannelContext)->get('count');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product count');
        }

        $offset = 0;

        while ($offset < $aggregate->getCount()) {
            $message = new ProductSingleSyncMessage();
            $message->setContext($salesChannelContext->getContext());
            $message->setSalesChannelContext($salesChannelContext);
            $message->setRunId($runId);
            $message->setLimit(self::CHUNK_SIZE);
            $message->setOffset($offset);
            $message->setSalesChannel($salesChannel);
            $this->messageBus->dispatch($message);

            $offset += self::CHUNK_SIZE;
        }
    }

    private function buildVariantMessages(
        Criteria $criteria,
        SalesChannelContext $salesChannelContext,
        SalesChannelEntity $salesChannel,
        string $runId
    ): void {
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
        foreach ($buckets as $bucket) {
            $ids[] = $bucket->getKey();

            /** @var SumResult|null $result */
            $result = $bucket->getResult();

            $chunkSize += $result !== null ? $result->getSum() : 2;

            if ($chunkSize >= self::CHUNK_SIZE) {
                $this->createVariantMessage($salesChannelContext, $runId, $salesChannel, $ids);
                $ids = [];
            }
        }

        if (\count($ids) > 0) {
            $this->createVariantMessage($salesChannelContext, $runId, $salesChannel, $ids);
        }
    }

    private function buildCleanupMessage(
        SalesChannelContext $salesChannelContext,
        SalesChannelEntity $salesChannel,
        string $runId
    ): void {
        $message = new ProductCleanupSyncMessage();
        $message->setContext($salesChannelContext->getContext());
        $message->setSalesChannelContext($salesChannelContext);
        $message->setRunId($runId);
        $message->setSalesChannel($salesChannel);
        $this->messageBus->dispatch($message);
    }

    private function createVariantMessage(SalesChannelContext $salesChannelContext, string $runId, SalesChannelEntity $salesChannel, array $ids): void
    {
        $message = new ProductVariantSyncMessage();
        $message->setContext($salesChannelContext->getContext());
        $message->setSalesChannelContext($salesChannelContext);
        $message->setRunId($runId);
        $message->setSalesChannel($salesChannel);
        $message->setIds($ids);
        $this->messageBus->dispatch($message);
    }
}
