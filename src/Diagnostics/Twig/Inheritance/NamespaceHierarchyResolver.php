<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Inheritance;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Twig\NamespaceHierarchy\BundleHierarchyBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SalesChannel\SalesChannelException;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeEntity;
use Shopware\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;

/**
 * Shopware builds the order only while rendering, so this combines its public builders.
 * Hierarchy builders of other extensions aren't applied.
 *
 * @internal
 */
#[Package('checkout')]
class NamespaceHierarchyResolver
{
    private const MAX_THEME_DEPTH = 10;
    private const STOREFRONT_THEME = 'Storefront';

    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     * @param EntityRepository<ThemeCollection>|null $themeRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
        private readonly ?EntityRepository $themeRepository,
        private readonly BundleHierarchyBuilder $bundleHierarchyBuilder,
        private readonly ?ThemeInheritanceBuilderInterface $themeInheritanceBuilder,
    ) {
    }

    /**
     * @return list<string>|null
     */
    public function resolveForStorefront(string $salesChannelId, Context $context): ?array
    {
        if ($this->themeRepository === null) {
            return null;
        }

        if ($this->loadSalesChannel($salesChannelId, $context)->getTypeId() !== Defaults::SALES_CHANNEL_TYPE_STOREFRONT) {
            return null;
        }

        // A storefront request without theme keeps the bundle order
        $themeName = $this->getThemeName($salesChannelId, $context);

        return $this->build($themeName === null ? [] : [$themeName => true, self::STOREFRONT_THEME => true]);
    }

    /**
     * Like Shopware's document rendering, the Storefront theme stands in when the Sales Channel has no theme.
     *
     * @return list<string>
     */
    public function resolveForDocuments(string $salesChannelId, Context $context): array
    {
        $this->loadSalesChannel($salesChannelId, $context);

        $themeName = $this->getThemeName($salesChannelId, $context);

        return $this->build($themeName === null ? [self::STOREFRONT_THEME => true] : [$themeName => true, self::STOREFRONT_THEME => true]);
    }

    /**
     * @param array<string, bool> $themes
     *
     * @return list<string>
     */
    private function build(array $themes): array
    {
        $hierarchy = $this->bundleHierarchyBuilder->buildNamespaceHierarchy([]);

        if ($themes !== [] && $this->themeInheritanceBuilder !== null) {
            $hierarchy = $this->themeInheritanceBuilder->build($hierarchy, $themes);
        }

        return \array_map(static fn (int|string $namespace) => (string) $namespace, \array_keys($hierarchy));
    }

    private function loadSalesChannel(string $salesChannelId, Context $context): SalesChannelEntity
    {
        $salesChannel = $this->salesChannelRepository->search(new Criteria([$salesChannelId]), $context)->getEntities()->first();

        if ($salesChannel === null) {
            throw SalesChannelException::salesChannelNotFound($salesChannelId);
        }

        return $salesChannel;
    }

    /**
     * Theme copies made in the Administration lack a technical name, so the original's is used.
     */
    private function getThemeName(string $salesChannelId, Context $context): ?string
    {
        if ($this->themeRepository === null) {
            return null;
        }

        $criteria = (new Criteria())->addFilter(new EqualsFilter('salesChannels.id', $salesChannelId));
        $theme = $this->themeRepository->search($criteria, $context)->getEntities()->first();

        for ($depth = 0; $theme instanceof ThemeEntity && $depth < self::MAX_THEME_DEPTH; ++$depth) {
            if ($theme->getTechnicalName() !== null) {
                return $theme->getTechnicalName();
            }

            $parentThemeId = $theme->getParentThemeId();
            if ($parentThemeId === null) {
                return null;
            }

            $theme = $this->themeRepository->search(new Criteria([$parentThemeId]), $context)->getEntities()->first();
        }

        return null;
    }
}
