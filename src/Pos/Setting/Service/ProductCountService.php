<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Setting\Service;

use Shopware\Core\Framework\Api\Exception\InvalidSalesChannelIdException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidAggregationQueryException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\CountAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Metric\CountResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\Pos\Resource\ProductResource;
use Swag\PayPal\Pos\Setting\Struct\ProductCount;
use Swag\PayPal\Pos\Sync\ProductSelection;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class ProductCountService
{
    private ProductResource $productResource;

    private ProductSelection $productSelection;

    private SalesChannelRepository $productRepository;

    private EntityRepository $salesChannelRepository;

    /**
     * @internal
     */
    public function __construct(
        ProductResource $productResource,
        ProductSelection $productSelection,
        SalesChannelRepository $productRepository,
        EntityRepository $salesChannelRepository,
    ) {
        $this->productResource = $productResource;
        $this->productSelection = $productSelection;
        $this->productRepository = $productRepository;
        $this->salesChannelRepository = $salesChannelRepository;
    }

    public function getProductCounts(string $salesChannelId, string $cloneSalesChannelId, Context $context): ProductCount
    {
        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        /** @var SalesChannelEntity|null $salesChannel */
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if ($salesChannel === null) {
            throw new InvalidSalesChannelIdException($salesChannelId);
        }

        $productCountResponse = new ProductCount();
        $productCountResponse->setLocalCount($this->getLocalCount($salesChannel, $cloneSalesChannelId, $context));
        $productCountResponse->setRemoteCount($this->getRemoteCount($salesChannel));

        return $productCountResponse;
    }

    private function getLocalCount(SalesChannelEntity $salesChannel, string $cloneSalesChannelId, Context $context): int
    {
        if ($cloneSalesChannelId === '') {
            return 0;
        }

        /** @var SalesChannelEntity|null $cloneSalesChannel */
        $cloneSalesChannel = $this->salesChannelRepository->search(new Criteria([$cloneSalesChannelId]), $context)->first();

        if ($cloneSalesChannel === null) {
            throw new InvalidSalesChannelIdException($cloneSalesChannelId);
        }

        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        $salesChannelContext = $this->productSelection->getSalesChannelContext($cloneSalesChannel);

        $criteria = $this->productSelection->getProductStreamCriteria($posSalesChannel->getProductStreamId(), $context);
        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addAggregation(new CountAggregation('count', 'id'));

        /** @var CountResult|null $aggregate */
        $aggregate = $this->productRepository->aggregate($criteria, $salesChannelContext)->get('count');
        if ($aggregate === null) {
            throw new InvalidAggregationQueryException('Could not aggregate product count');
        }

        return $aggregate->getCount();
    }

    private function getRemoteCount(SalesChannelEntity $salesChannel): int
    {
        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        return $this->productResource->getProductCount($posSalesChannel)->getProductCount();
    }
}
