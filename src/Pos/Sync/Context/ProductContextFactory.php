<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Pos\Sync\Context;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelMediaCollection;
use Swag\PayPal\Pos\DataAbstractionLayer\Entity\PosSalesChannelProductCollection;

class ProductContextFactory
{
    /**
     * @var EntityRepositoryInterface
     */
    private $posProductRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $posMediaRepository;

    /**
     * @var ProductContext[]
     */
    private $productContexts = [];

    public function __construct(
        EntityRepositoryInterface $posProductRepository,
        EntityRepositoryInterface $posMediaRepository
    ) {
        $this->posProductRepository = $posProductRepository;
        $this->posMediaRepository = $posMediaRepository;
    }

    public function getContext(SalesChannelEntity $salesChannel, Context $context): ProductContext
    {
        if (isset($this->productContexts[$salesChannel->getId()])) {
            return $this->productContexts[$salesChannel->getId()];
        }

        $posProductCollection = $this->getPosProductCollection($salesChannel->getId(), $context);
        $posMediaCollection = $this->getPosMediaCollection($salesChannel->getId(), $context);

        $inventoryContext = new ProductContext(
            $salesChannel,
            $posProductCollection,
            $posMediaCollection,
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

    private function getPosProductCollection(string $salesChannelId, Context $context): PosSalesChannelProductCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        /** @var PosSalesChannelProductCollection $posProductCollection */
        $posProductCollection = $this->posProductRepository->search($criteria, $context)->getEntities();

        return $posProductCollection;
    }

    private function getPosMediaCollection(string $salesChannelId, Context $context): PosSalesChannelMediaCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));

        /** @var PosSalesChannelMediaCollection $posMediaCollection */
        $posMediaCollection = $this->posMediaRepository->search($criteria, $context)->getEntities();

        return $posMediaCollection;
    }
}
