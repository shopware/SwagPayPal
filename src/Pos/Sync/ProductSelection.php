<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync;

use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelEntity;
use Swag\PayPal\SwagPayPal;

#[Package('checkout')]
class ProductSelection
{
    private SalesChannelRepository $productRepository;

    private ProductStreamBuilderInterface $productStreamBuilder;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    /**
     * @internal
     */
    public function __construct(
        SalesChannelRepository $productRepository,
        ProductStreamBuilderInterface $productStreamBuilder,
        AbstractSalesChannelContextFactory $salesChannelContextFactory,
    ) {
        $this->productRepository = $productRepository;
        $this->productStreamBuilder = $productStreamBuilder;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
    }

    public function getProductIds(
        SalesChannelEntity $salesChannel,
        Context $context,
    ): array {
        $salesChannelContext = $this->getSalesChannelContext($salesChannel);

        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        $productStreamId = $posSalesChannel->getProductStreamId();
        $criteria = $this->getProductStreamCriteria($productStreamId, $context);

        return $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();
    }

    public function getProductLogCollection(
        SalesChannelEntity $salesChannel,
        int $offset,
        int $limit,
        Context $context,
    ): EntitySearchResult {
        $salesChannelContext = $this->getSalesChannelContext($salesChannel);

        /** @var PosSalesChannelEntity $posSalesChannel */
        $posSalesChannel = $salesChannel->getExtension(SwagPayPal::SALES_CHANNEL_POS_EXTENSION);

        $productStreamId = $posSalesChannel->getProductStreamId();
        $criteria = $this->getProductStreamCriteria($productStreamId, $context);

        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addSorting(
            new FieldSorting(SwagPayPal::PRODUCT_LOG_POS_EXTENSION . '.posSalesChannelRun.createdAt', FieldSorting::DESCENDING),
            new FieldSorting(SwagPayPal::PRODUCT_LOG_POS_EXTENSION . '.level', FieldSorting::DESCENDING)
        );

        $criteria->addAssociation(SwagPayPal::PRODUCT_LOG_POS_EXTENSION . '.posSalesChannelRun');
        $logAssociation = $criteria->getAssociation(SwagPayPal::PRODUCT_LOG_POS_EXTENSION);
        $logAssociation->addSorting(
            new FieldSorting('createdAt', FieldSorting::DESCENDING),
            new FieldSorting('level', FieldSorting::DESCENDING)
        );
        $logAssociation->setLimit(1);
        $logAssociation->addFilter(new EqualsFilter('posSalesChannelRun.salesChannelId', $posSalesChannel->getSalesChannelId()));

        $criteria->addAssociation(SwagPayPal::PRODUCT_SYNC_POS_EXTENSION);
        $syncAssociation = $criteria->getAssociation(SwagPayPal::PRODUCT_SYNC_POS_EXTENSION);
        $syncAssociation->addFilter(new EqualsFilter('salesChannelId', $posSalesChannel->getSalesChannelId()));

        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        $criteria->setOffset($offset);
        $criteria->setLimit($limit);

        return $this->productRepository->search($criteria, $salesChannelContext);
    }

    public function getProductStreamCriteria(?string $productStreamId, Context $context): Criteria
    {
        if (!$productStreamId) {
            return new Criteria();
        }

        $filters = $this->productStreamBuilder->buildFilters(
            $productStreamId,
            $context
        );

        $criteria = new Criteria();
        $criteria->addFilter(...$filters);

        return $criteria;
    }

    public function getSalesChannelContext(SalesChannelEntity $salesChannel): SalesChannelContext
    {
        return $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $salesChannel->getId()
        );
    }

    public function addAssociations(Criteria $criteria): void
    {
        $criteria
            ->addAssociation('categories')
            ->addAssociation('cover.media')
            ->addAssociation('prices')
            ->addAssociation('configuratorSettings.option.group')
            ->addAssociation('options.group');
    }
}
