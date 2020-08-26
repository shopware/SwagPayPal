<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Run\Administration;

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
    private $iZettleProductRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleInventoryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleMediaRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleRunRepository;

    public function __construct(
        EntityRepositoryInterface $iZettleProductRepository,
        EntityRepositoryInterface $iZettleInventoryRepository,
        EntityRepositoryInterface $iZettleMediaRepository,
        EntityRepositoryInterface $iZettleRunRepository
    ) {
        $this->iZettleProductRepository = $iZettleProductRepository;
        $this->iZettleInventoryRepository = $iZettleInventoryRepository;
        $this->iZettleMediaRepository = $iZettleMediaRepository;
        $this->iZettleRunRepository = $iZettleRunRepository;
    }

    public function resetSync(SalesChannelEntity $salesChannel, Context $context): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannel->getId()));

        $ids = $this->iZettleProductRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'productId' => $id['product_id']];
            }, $ids);
            $this->iZettleProductRepository->delete($ids, $context);
        }

        $ids = $this->iZettleInventoryRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'productId' => $id['product_id']];
            }, $ids);
            $this->iZettleInventoryRepository->delete($ids, $context);
        }

        $ids = $this->iZettleMediaRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_array($id)) {
                    return null;
                }

                return ['salesChannelId' => $id['sales_channel_id'], 'mediaId' => $id['media_id']];
            }, $ids);
            $this->iZettleMediaRepository->delete($ids, $context);
        }

        $ids = $this->iZettleRunRepository->searchIds($criteria, $context)->getIds();
        if (!empty($ids)) {
            $ids = \array_map(static function ($id) {
                if (!\is_string($id)) {
                    return null;
                }

                return ['id' => $id];
            }, $ids);
            $this->iZettleRunRepository->delete($ids, $context);
        }
    }
}
