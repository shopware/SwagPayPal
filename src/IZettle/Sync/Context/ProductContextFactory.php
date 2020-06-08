<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Sync\Context;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelMediaCollection;
use Swag\PayPal\IZettle\DataAbstractionLayer\Entity\IZettleSalesChannelProductCollection;

class ProductContextFactory
{
    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleProductRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $iZettleMediaRepository;

    /**
     * @var ProductContext[]
     */
    private $productContexts = [];

    public function __construct(
        EntityRepositoryInterface $iZettleProductRepository,
        EntityRepositoryInterface $iZettleMediaRepository
    ) {
        $this->iZettleProductRepository = $iZettleProductRepository;
        $this->iZettleMediaRepository = $iZettleMediaRepository;
    }

    public function getContext(SalesChannelEntity $salesChannel, Context $context): ProductContext
    {
        if (isset($this->productContexts[$salesChannel->getId()])) {
            return $this->productContexts[$salesChannel->getId()];
        }

        $iZettleProductCollection = $this->getIZettleProductCollection($salesChannel->getId(), $context);
        $iZettleMediaCollection = $this->getIZettleMediaCollection($salesChannel->getId(), $context);

        $inventoryContext = new ProductContext(
            $salesChannel,
            $iZettleProductCollection,
            $iZettleMediaCollection,
            $context
        );

        $this->productContexts[$salesChannel->getId()] = $inventoryContext;

        return $inventoryContext;
    }

    public function commit(ProductContext $productContext): void
    {
        $updatedProducts = $productContext->getProductChanges();
        $removedProducts = $productContext->getProductRemovals();
        $mediaRequests = $productContext->getMediaRequests();

        if ($updatedProducts) {
            $this->iZettleProductRepository->upsert($updatedProducts, $productContext->getContext());
        }

        if ($removedProducts) {
            $this->iZettleProductRepository->delete($removedProducts, $productContext->getContext());
        }

        if ($mediaRequests) {
            $this->iZettleMediaRepository->create($mediaRequests, $productContext->getContext());
        }

        $productContext->resetChanges();

        $productContext->setIZettleProductCollection(
            $this->getIZettleProductCollection($productContext->getSalesChannel()->getId(), $productContext->getContext())
        );
    }

    private function getIZettleProductCollection(string $salesChannelId, Context $context): IZettleSalesChannelProductCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        /** @var IZettleSalesChannelProductCollection $iZettleProductCollection */
        $iZettleProductCollection = $this->iZettleProductRepository->search($criteria, $context)->getEntities();

        return $iZettleProductCollection;
    }

    private function getIZettleMediaCollection(string $salesChannelId, Context $context): IZettleSalesChannelMediaCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        /** @var IZettleSalesChannelMediaCollection $iZettleMediaCollection */
        $iZettleMediaCollection = $this->iZettleMediaRepository->search($criteria, $context)->getEntities();

        return $iZettleMediaCollection;
    }
}
