<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Compatibility;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEventFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\AggregationResultCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Write\CloneBehavior;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * @internal
 *
 * required until min version 6.5
 */
if (\interface_exists(SalesChannelRepositoryInterface::class)) {
    // @phpstan-ignore-next-line SalesChannelRepository is final, but we only extend it in 6.4, so we are fine
    class SalesChannelRepositoryDecorator extends SalesChannelRepository implements SalesChannelRepositoryInterface
    {
        private SalesChannelRepositoryInterface $inner;

        public function __construct(SalesChannelRepositoryInterface $inner)
        {
            $this->inner = $inner;
        }

        public function search(Criteria $criteria, SalesChannelContext $context): EntitySearchResult
        {
            return $this->inner->search($criteria, $context);
        }

        public function aggregate(Criteria $criteria, SalesChannelContext $context): AggregationResultCollection
        {
            return $this->inner->aggregate($criteria, $context);
        }

        public function searchIds(Criteria $criteria, SalesChannelContext $context): IdSearchResult
        {
            return $this->inner->searchIds($criteria, $context);
        }
    }
}
