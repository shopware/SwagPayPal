<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Administration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

#[Package('checkout')]
class SyncResetter
{
    /**
     * @internal
     */
    public function __construct(
        private readonly EntityRepository $posProductRepository,
        private readonly EntityRepository $posInventoryRepository,
        private readonly EntityRepository $posMediaRepository,
        private readonly EntityRepository $posRunRepository,
    ) {
    }

    public function resetSync(SalesChannelEntity $salesChannel, Context $context): void
    {
        /** @var Criteria<array<string, string>> $criteria */
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel->getId()));

        $ids = $this->posProductRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $this->posProductRepository->delete(\array_filter($ids), $context);
        }

        $ids = $this->posInventoryRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $this->posInventoryRepository->delete(\array_filter($ids), $context);
        }

        $ids = $this->posMediaRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $this->posMediaRepository->delete(\array_filter($ids), $context);
        }

        /** @phpstan-ignore varTag.type */ /** @var Criteria<string> $criteria */
        $ids = $this->posRunRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static fn ($id) => ['id' => $id], $ids);
            $this->posRunRepository->delete(\array_filter($ids), $context);
        }
    }
}
