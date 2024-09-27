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
    private EntityRepository $posProductRepository;

    private EntityRepository $posInventoryRepository;

    private EntityRepository $posMediaRepository;

    private EntityRepository $posRunRepository;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $posProductRepository,
        EntityRepository $posInventoryRepository,
        EntityRepository $posMediaRepository,
        EntityRepository $posRunRepository,
    ) {
        $this->posProductRepository = $posProductRepository;
        $this->posInventoryRepository = $posInventoryRepository;
        $this->posMediaRepository = $posMediaRepository;
        $this->posRunRepository = $posRunRepository;
    }

    public function resetSync(SalesChannelEntity $salesChannel, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel->getId()));

        $ids = $this->posProductRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_filter($ids, static fn ($id) => \is_array($id));
            $this->posProductRepository->delete(\array_filter($ids), $context);
        }

        $ids = $this->posInventoryRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_filter($ids, static fn ($id) => \is_array($id));
            $this->posInventoryRepository->delete(\array_filter($ids), $context);
        }

        $ids = $this->posMediaRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_filter($ids, static fn ($id) => \is_array($id));
            $this->posMediaRepository->delete(\array_filter($ids), $context);
        }

        $ids = $this->posRunRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_string($id)) {
                    return null;
                }

                return ['id' => $id];
            }, $ids);
            $this->posRunRepository->delete(\array_filter($ids), $context);
        }
    }
}
