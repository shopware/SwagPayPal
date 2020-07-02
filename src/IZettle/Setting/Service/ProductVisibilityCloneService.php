<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\IZettle\Setting\Service;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ProductVisibilityCloneService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productVisibilityRepository;

    public function __construct(EntityRepositoryInterface $productVisibilityRepository)
    {
        $this->productVisibilityRepository = $productVisibilityRepository;
    }

    public function cloneProductVisibility(
        string $fromSalesChannelId,
        string $toSalesChannelId,
        Context $context
    ): void {
        $criteria = new Criteria();
        $criteria->addAssociation('product');
        $criteria->addFilter(new EqualsFilter('salesChannelId', $fromSalesChannelId));
        $existingVisibility = $this->productVisibilityRepository->search($criteria, $context)->getEntities();

        $updates = [];
        foreach ($existingVisibility->getElements() as $visibilityElement) {
            /** @var ProductEntity $product */
            $product = $visibilityElement->getProduct();
            $updates[] = [
                'productId' => $product->getId(),
                'productVersionId' => $product->getVersionId(),
                'salesChannelId' => $toSalesChannelId,
                'visibility' => $visibilityElement->getVisibility(),
            ];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $toSalesChannelId));

        $formerVisibilityIds = $this->productVisibilityRepository->searchIds($criteria, $context)->getIds();
        if (\count($formerVisibilityIds) > 0) {
            $this->productVisibilityRepository->delete($formerVisibilityIds, $context);
        }
        $this->productVisibilityRepository->upsert($updates, $context);
    }
}
