<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilderInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelDomain\SalesChannelDomainEntity;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;
use Swag\PayPal\IZettle\Exception\NoDomainAssignedException;
use Swag\PayPal\SwagPayPal;

class ProductSelection
{
    /**
     * @var SalesChannelRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductStreamBuilderInterface
     */
    private $productStreamBuilder;

    /**
     * @var EntityRepositoryInterface
     */
    private $domainRepository;

    /**
     * @var SalesChannelContextFactory
     */
    private $salesChannelContextFactory;

    public function __construct(
        SalesChannelRepositoryInterface $productRepository,
        ProductStreamBuilderInterface $productStreamBuilder,
        EntityRepositoryInterface $domainRepository,
        SalesChannelContextFactory $salesChannelContextFactory
    ) {
        $this->productRepository = $productRepository;
        $this->productStreamBuilder = $productStreamBuilder;
        $this->domainRepository = $domainRepository;
        $this->salesChannelContextFactory = $salesChannelContextFactory;
    }

    public function getProductCollection(
        SalesChannelEntity $salesChannel,
        Context $context,
        bool $addAssociations
    ): ProductCollection {
        $salesChannelContext = $this->getSalesChannelContext($salesChannel, $context);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension('paypalIZettleSalesChannel');

        $productStreamId = $iZettleSalesChannel->getProductStreamId();
        $criteria = $this->getProductStreamCriteria($productStreamId, $context);

        if ($addAssociations) {
            $this->addAssociations($criteria);
        }

        /** @var ProductCollection $shopwareProducts */
        $shopwareProducts = $this->productRepository->search($criteria, $salesChannelContext)->getEntities();

        return $shopwareProducts;
    }

    public function getProductLogCollection(
        SalesChannelEntity $salesChannel,
        int $offset,
        int $limit,
        Context $context
    ): EntitySearchResult {
        $salesChannelContext = $this->getSalesChannelContext($salesChannel, $context);

        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension('paypalIZettleSalesChannel');

        $productStreamId = $iZettleSalesChannel->getProductStreamId();
        $criteria = $this->getProductStreamCriteria($productStreamId, $context);

        $criteria->addFilter(new EqualsFilter('parentId', null));
        $criteria->addFilter(new EqualsFilter(
            SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION . '.run.salesChannelId',
            $iZettleSalesChannel->getSalesChannelId()
        ));

        $criteria->addAssociation(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION . '.run');
        $criteria->getAssociation(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION)->addSorting(
            new FieldSorting('createdAt', FieldSorting::DESCENDING),
            new FieldSorting('level', FieldSorting::DESCENDING)
        );
        $criteria->getAssociation(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION)->setLimit(1);
        $criteria->addSorting(
            new FieldSorting(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION . '.run.createdAt', FieldSorting::DESCENDING),
            new FieldSorting(SwagPayPal::PRODUCT_LOG_IZETTLE_EXTENSION . '.level', FieldSorting::DESCENDING)
        );
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        $criteria->setOffset($offset);
        $criteria->setLimit($limit);

        return $this->productRepository->search($criteria, $salesChannelContext);
    }

    private function getProductStreamCriteria(?string $productStreamId, Context $context): Criteria
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

    private function getSalesChannelContext(SalesChannelEntity $salesChannel, Context $context): SalesChannelContext
    {
        /** @var IZettleSalesChannelEntity $iZettleSalesChannel */
        $iZettleSalesChannel = $salesChannel->getExtension('paypalIZettleSalesChannel');

        $criteria = new Criteria();
        $criteria->setIds([$iZettleSalesChannel->getSalesChannelDomainId()]);

        /** @var SalesChannelDomainEntity|null $domain */
        $domain = $this->domainRepository->search($criteria, $context)->first();

        if ($domain === null) {
            throw new NoDomainAssignedException($salesChannel->getId());
        }

        return $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $domain->getSalesChannelId(),
            [
                SalesChannelContextService::LANGUAGE_ID => $domain->getLanguageId(),
                SalesChannelContextService::CURRENCY_ID => $salesChannel->getCurrencyId(),
            ]
        );
    }

    private function addAssociations(Criteria $criteria): void
    {
        $criteria
            ->addAssociation('categories')
            ->addAssociation('cover.media')
            ->addAssociation('prices')
            ->addAssociation('translation')
            ->addAssociation('configuratorSettings.option.translation')
            ->addAssociation('configuratorSettings.option.group.translation')
            ->addAssociation('options.translation')
            ->addAssociation('options.group.translation');
    }
}
