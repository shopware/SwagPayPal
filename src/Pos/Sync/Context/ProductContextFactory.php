<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Context;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductCollection;

#[Package('checkout')]
class ProductContextFactory
{
    private EntityRepository $posProductRepository;

    private EntityRepository $posMediaRepository;

    /**
     * @internal
     */
    public function __construct(
        EntityRepository $posProductRepository,
        EntityRepository $posMediaRepository,
    ) {
        $this->posProductRepository = $posProductRepository;
        $this->posMediaRepository = $posMediaRepository;
    }

    public function getContext(SalesChannelEntity $salesChannel, Context $context, ?ProductCollection $productCollection = null): ProductContext
    {
        $posProductCollection = $this->getPosProductCollection(
            $salesChannel->getId(),
            $context,
            $productCollection !== null ? $productCollection->fmap(function (ProductEntity $product) {
                return $product->getId();
            }) : null
        );

        $posMediaCollection = $this->getPosMediaCollection(
            $salesChannel->getId(),
            $context,
            $productCollection !== null ? $productCollection->fmap(function (ProductEntity $product) {
                $cover = $product->getCover();

                return $cover !== null ? $cover->getMediaId() : null;
            }) : null
        );

        return new ProductContext(
            $salesChannel,
            $posProductCollection,
            $posMediaCollection,
            $context
        );
    }

    public function commit(ProductContext $productContext): void
    {
        $updatedProducts = $productContext->getProductChanges();
        $removedProducts = $productContext->getProductRemovals();
        $mediaRequests = $productContext->getMediaRequests();

        if ($updatedProducts) {
            $this->posProductRepository->upsert($updatedProducts, $productContext->getContext());
        }

        if ($removedProducts) {
            $this->posProductRepository->delete($removedProducts, $productContext->getContext());
        }

        if ($mediaRequests) {
            $this->posMediaRepository->create($mediaRequests, $productContext->getContext());
        }

        $productContext->resetChanges();

        $productContext->setPosProductCollection(
            $this->getPosProductCollection($productContext->getSalesChannel()->getId(), $productContext->getContext())
        );
    }

    private function getPosProductCollection(string $salesChannelId, Context $context, ?array $ids = null): PosSalesChannelProductCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        if ($ids !== null) {
            $criteria->addFilter(new EqualsAnyFilter('productId', $ids));
        }

        /** @var PosSalesChannelProductCollection $posProductCollection */
        $posProductCollection = $this->posProductRepository->search($criteria, $context)->getEntities();

        return $posProductCollection;
    }

    private function getPosMediaCollection(string $salesChannelId, Context $context, ?array $ids = null): PosSalesChannelMediaCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        if ($ids !== null) {
            $criteria->addFilter(new EqualsAnyFilter('mediaId', $ids));
        }

        /** @var PosSalesChannelMediaCollection $posMediaCollection */
        $posMediaCollection = $this->posMediaRepository->search($criteria, $context)->getEntities();

        return $posMediaCollection;
    }
}
