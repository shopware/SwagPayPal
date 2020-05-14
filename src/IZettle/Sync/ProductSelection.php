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
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelEntity;

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

    public function getProducts(
        IZettleSalesChannelEntity $iZettleSalesChannel,
        Context $context,
        bool $addAssociations
    ): ProductCollection {
        $salesChannelContext = $this->getSalesChannelContext($iZettleSalesChannel, $context);

        $productStreamId = $iZettleSalesChannel->getProductStreamId();
        if ($productStreamId) {
            $criteria = $this->getProductStreamCriteria($productStreamId, $context);
        } else {
            $criteria = new Criteria();
        }

        if ($addAssociations) {
            $this->addAssociations($criteria);
        }

        /** @var ProductCollection $shopwareProducts */
        $shopwareProducts = $this->productRepository->search($criteria, $salesChannelContext)->getEntities();

        return $shopwareProducts;
    }

    private function getProductStreamCriteria(string $productStreamId, Context $context): Criteria
    {
        $filters = $this->productStreamBuilder->buildFilters(
            $productStreamId,
            $context
        );

        $criteria = new Criteria();
        $criteria->addFilter(...$filters);

        return $criteria;
    }

    private function addAssociations(Criteria $criteria): void
    {
        $criteria
            ->addAssociation('categories')
            ->addAssociation('cover')
            ->addAssociation('prices')
            ->addAssociation('translation')
            ->addAssociation('configuratorSettings.option.translation')
            ->addAssociation('configuratorSettings.option.group.translation')
            ->addAssociation('options.translation')
            ->addAssociation('options.group.translation');
    }

    private function getSalesChannelContext(IZettleSalesChannelEntity $iZettleSalesChannel, Context $context): SalesChannelContext
    {
        $criteria = new Criteria();
        $criteria->setIds([$iZettleSalesChannel->getSalesChannelDomainId()]);

        $domain = $this->domainRepository->search($criteria, $context)->first();

        return $this->salesChannelContextFactory->create(
            Uuid::randomHex(),
            $domain->getSalesChannelId()
        );
    }
}
