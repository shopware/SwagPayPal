<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Swag\PayPal\IZettle\MessageQueue\Message\CloneVisibilityMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductVisibilityCloneService
{
    private const CLONE_CHUNK_SIZE = 500;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * @var EntityRepositoryInterface
     */
    private $productVisibilityRepository;

    public function __construct(
        MessageBusInterface $messageBus,
        EntityRepositoryInterface $productVisibilityRepository
    ) {
        $this->messageBus = $messageBus;
        $this->productVisibilityRepository = $productVisibilityRepository;
    }

    public function cloneProductVisibility(
        string $fromSalesChannelId,
        string $toSalesChannelId,
        Context $context
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

        while ($offset < $aggregate->getCount()) {
            $message = new CloneVisibilityMessage();
            $message->setContext($context);
            $message->setLimit(self::CLONE_CHUNK_SIZE);
            $message->setOffset($offset);
            $message->setFromSalesChannelId($fromSalesChannelId);
            $message->setToSalesChannelId($toSalesChannelId);
            $this->messageBus->dispatch($message);

            $offset += self::CLONE_CHUNK_SIZE;
        }
    }
}
