<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Run\Administration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

class SyncResetter
{
    /**
     * @var EntityRepositoryInterface
     */
    private $posProductRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $posInventoryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $posMediaRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $posRunRepository;

    public function __construct(
        EntityRepositoryInterface $posProductRepository,
        EntityRepositoryInterface $posInventoryRepository,
        EntityRepositoryInterface $posMediaRepository,
        EntityRepositoryInterface $posRunRepository
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
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'productId' => $id['product_id']];
            }, $ids);
            $this->posProductRepository->delete($ids, $context);
        }

        $ids = $this->posInventoryRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'productId' => $id['product_id']];
            }, $ids);
            $this->posInventoryRepository->delete($ids, $context);
        }

        $ids = $this->posMediaRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'mediaId' => $id['media_id']];
            }, $ids);
            $this->posMediaRepository->delete($ids, $context);
        }

        $ids = $this->posRunRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_string($id)) {
                    return null;
                }

                return ['id' => $id];
            }, $ids);
            $this->posRunRepository->delete($ids, $context);
        }
    }
}
