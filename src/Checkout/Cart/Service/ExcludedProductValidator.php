<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Cart\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class ExcludedProductValidator
{
    public const PRODUCT_EXCLUDED_FOR_PAYPAL = 'swagPayPalIsExcludedProduct';

    private SystemConfigService $systemConfigService;

    private SalesChannelRepository $productRepository;

    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        SalesChannelRepository $productRepository,
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->productRepository = $productRepository;
    }

    public function cartContainsExcludedProduct(Cart $cart, SalesChannelContext $salesChannelContext): bool
    {
        $productIds = [];
        $excludedProductIds = $this->systemConfigService->get(Settings::EXCLUDED_PRODUCT_IDS, $salesChannelContext->getSalesChannelId()) ?? [];
        if (!\is_array($excludedProductIds)) {
            $excludedProductIds = [];
        }

        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }

            $referencedId = $lineItem->getReferencedId();
            if ($referencedId === null) {
                continue;
            }

            if (\in_array($referencedId, $excludedProductIds, true)) {
                return true;
            }

            $productIds[] = $referencedId;
        }

        if (!$productIds) {
            return false;
        }

        if ($excludedProductIds) {
            $criteria = new Criteria($productIds);
            $criteria->addFilter(new EqualsAnyFilter('parentId', $excludedProductIds));
            $criteria->setLimit(1);

            if ($this->productRepository->searchIds($criteria, $salesChannelContext)->firstId()) {
                return true;
            }
        }

        $excludedProductStreamIds = $this->systemConfigService->get(Settings::EXCLUDED_PRODUCT_STREAM_IDS, $salesChannelContext->getSalesChannelId()) ?? [];
        if (!\is_array($excludedProductStreamIds) || empty($excludedProductStreamIds)) {
            return false;
        }

        $criteria = new Criteria($productIds);
        $criteria->addFilter(new MultiFilter(MultiFilter::CONNECTION_OR, [
            new EqualsAnyFilter('streams.id', $excludedProductStreamIds),
            new EqualsAnyFilter('parent.streams.id', $excludedProductStreamIds),
        ]));
        $criteria->setLimit(1);

        return (bool) $this->productRepository->searchIds($criteria, $salesChannelContext)->firstId();
    }

    /**
     * @param string[] $productIds needs to also contain parent ids
     *
     * @return string[] the excluded product ids
     */
    public function findExcludedProducts(array $productIds, SalesChannelContext $salesChannelContext): array
    {
        $productIds = \array_values(\array_filter($productIds));
        if (empty($productIds)) {
            return [];
        }

        $excludedProductIds = $this->systemConfigService->get(Settings::EXCLUDED_PRODUCT_IDS, $salesChannelContext->getSalesChannelId()) ?? [];
        $excludedByProductIds = [];
        if (\is_array($excludedProductIds)) {
            $excludedByProductIds = \array_intersect($productIds, $excludedProductIds);
        }

        $excludedProductStreamIds = $this->systemConfigService->get(Settings::EXCLUDED_PRODUCT_STREAM_IDS, $salesChannelContext->getSalesChannelId()) ?? [];
        if (!\is_array($excludedProductStreamIds) || empty($excludedProductStreamIds)) {
            return $excludedByProductIds;
        }

        $criteria = new Criteria($productIds);
        $criteria->addAssociation('streams');
        $criteria->addFilter(new EqualsAnyFilter('streams.id', $excludedProductStreamIds));
        /** @var string[] $excludedByProductStream */
        $excludedByProductStream = $this->productRepository->searchIds($criteria, $salesChannelContext)->getIds();

        return \array_unique(\array_merge($excludedByProductIds, $excludedByProductStream));
    }

    public function isProductExcluded(ProductEntity $product, SalesChannelContext $salesChannelContext): bool
    {
        return !empty($this->findExcludedProducts(
            \array_filter([
                $product->getId(),
                $product->getParentId(),
            ]),
            $salesChannelContext
        ));
    }
}
