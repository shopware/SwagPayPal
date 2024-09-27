<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Routing\Exception\SalesChannelNotFoundException;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\MessageQueue\Handler\SyncManagerHandler;
use Swag\PayPal\Pos\MessageQueue\Message\CloneVisibilityMessage;
use Swag\PayPal\Pos\MessageQueue\MessageDispatcher;
use Swag\PayPal\Pos\Run\RunService;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class ProductVisibilityCloneService
{
    private const CLONE_CHUNK_SIZE = 500;

    private MessageDispatcher $messageBus;

    private EntityRepository $productVisibilityRepository;

    private RunService $runService;

    private EntityRepository $salesChannelRepository;

    /**
     * @internal
     */
    public function __construct(
        MessageDispatcher $messageBus,
        EntityRepository $productVisibilityRepository,
        RunService $runService,
        EntityRepository $salesChannelRepository,
    ) {
        $this->messageBus = $messageBus;
        $this->productVisibilityRepository = $productVisibilityRepository;
        $this->runService = $runService;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function cloneProductVisibility(
        string $fromSalesChannelId,
        string $toSalesChannelId,
        Context $context,
    ): void {
        $deletionCriteria = new Criteria();
        $deletionCriteria->addFilter(new EqualsFilter('salesChannelId', $toSalesChannelId));

        /** @var string[] $formerVisibilityIds */
        $formerVisibilityIds = $this->productVisibilityRepository->searchIds($deletionCriteria, $context)->getIds();
        if (\count($formerVisibilityIds) > 0) {
            $formerVisibilityIds = \array_map(static function (string $id) {
                return ['id' => $id];
            }, $formerVisibilityIds);
            $this->productVisibilityRepository->delete($formerVisibilityIds, $context);
        }

        $offset = 0;

        $aggregationCriteria = new Criteria();
        $aggregationCriteria->addFilter(new EqualsFilter('salesChannelId', $fromSalesChannelId));
        $aggregationCriteria->addAggregation(new CountAggregation('count', 'productId'));

        /** @var CountResult|null $aggregate */
        $aggregate = $this->productVisibilityRepository->aggregate($aggregationCriteria, $context)->get('count');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product visibility');
        }

        $runId = $this->runService->startRun($toSalesChannelId, 'cloneVisibility', [SyncManagerHandler::SYNC_CLONE_VISIBILITY], $context);

        $criteria = new Criteria([$toSalesChannelId]);
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);
        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new SalesChannelNotFoundException();
        }

        $messages = [];
        while ($offset < $aggregate->getCount()) {
            $message = new CloneVisibilityMessage();
            $message->setLimit(self::CLONE_CHUNK_SIZE);
            $message->setOffset($offset);
            $message->setFromSalesChannelId($fromSalesChannelId);
            $message->setToSalesChannelId($toSalesChannelId);
            $message->setSalesChannel($salesChannel);
            $message->setRunId($runId);
            $messages[] = $message;

            $offset += self::CLONE_CHUNK_SIZE;
        }

        $this->messageBus->bulkDispatch($messages, $runId);
    }
}
