<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Inheritance;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Adapter\Twig\NamespaceHierarchy\BundleHierarchyBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SalesChannel\SalesChannelException;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\TestDefaults;
use Shopware\Storefront\Theme\ThemeCollection;
use Shopware\Storefront\Theme\ThemeEntity;
use Shopware\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;
use Swag\PayPal\Diagnostics\Twig\Inheritance\NamespaceHierarchyResolver;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(NamespaceHierarchyResolver::class)]
class NamespaceHierarchyResolverTest extends TestCase
{
    private const BUNDLES = ['MyTheme' => 0, 'SwagPayPal' => 0, 'Storefront' => 0];

    private BundleHierarchyBuilder&MockObject $bundleHierarchyBuilder;

    private ThemeInheritanceBuilderInterface&MockObject $themeInheritanceBuilder;

    protected function setUp(): void
    {
        $this->bundleHierarchyBuilder = $this->createMock(BundleHierarchyBuilder::class);
        $this->bundleHierarchyBuilder->method('buildNamespaceHierarchy')->willReturn(self::BUNDLES);

        $this->themeInheritanceBuilder = $this->createMock(ThemeInheritanceBuilderInterface::class);
    }

    public function testThrowsForUnknownSalesChannels(): void
    {
        $resolver = $this->createResolver(new SalesChannelCollection(), $this->themeRepository([]));

        $this->expectExceptionObject(SalesChannelException::salesChannelNotFound(TestDefaults::SALES_CHANNEL));

        $resolver->resolveForStorefront(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());
    }

    public function testSalesChannelsWithoutStorefrontHaveNoHierarchy(): void
    {
        $resolver = $this->createResolver(
            new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_API)]),
            $this->themeRepository([]),
        );

        static::assertNull($resolver->resolveForStorefront(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
    }

    public function testNoHierarchyWithoutTheStorefrontBundle(): void
    {
        $resolver = new NamespaceHierarchyResolver(
            $this->salesChannelRepository(),
            null,
            $this->bundleHierarchyBuilder,
            null,
        );

        static::assertNull($resolver->resolveForStorefront(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
    }

    public function testKeepsTheBundleOrderWithoutTheme(): void
    {
        $this->themeInheritanceBuilder->expects($this->never())->method('build');

        $resolver = $this->createResolver(
            new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)]),
            $this->themeRepository([new ThemeCollection()]),
        );

        static::assertSame(['MyTheme', 'SwagPayPal', 'Storefront'], $resolver->resolveForStorefront(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
    }

    public function testTheSalesChannelThemeRearrangesTheBundles(): void
    {
        $this->themeInheritanceBuilder->expects($this->once())
            ->method('build')
            ->with(self::BUNDLES, ['MyTheme' => true, 'Storefront' => true])
            ->willReturn(['SwagPayPal' => 0, 'MyTheme' => 0, 'Storefront' => 0]);

        $searchedCriteria = [];
        $themeRepository = $this->themeRepository([new ThemeCollection([$this->theme(technicalName: 'MyTheme')])], $searchedCriteria);
        $resolver = $this->createResolver(
            new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)]),
            $themeRepository,
        );

        static::assertSame(['SwagPayPal', 'MyTheme', 'Storefront'], $resolver->resolveForStorefront(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
        static::assertEquals([new EqualsFilter('salesChannels.id', TestDefaults::SALES_CHANNEL)], $searchedCriteria[0]->getFilters());
    }

    public function testThemeCopyUsesTheTemplatesOfTheThemeItWasCopiedFrom(): void
    {
        $this->themeInheritanceBuilder->expects($this->once())
            ->method('build')
            ->with(self::BUNDLES, ['MyTheme' => true, 'Storefront' => true])
            ->willReturn(self::BUNDLES);

        $original = $this->theme(technicalName: 'MyTheme');
        $searchedCriteria = [];
        $themeRepository = $this->themeRepository([
            new ThemeCollection([$this->theme(parentThemeId: $original->getId())]),
            new ThemeCollection([$original]),
        ], $searchedCriteria);

        $this->createResolver(
            new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)]),
            $themeRepository,
        )->resolveForStorefront(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());

        static::assertSame([$original->getId()], $searchedCriteria[1]->getIds());
    }

    public function testDocumentsOfHeadlessSalesChannelsUseTheStorefrontTheme(): void
    {
        $this->themeInheritanceBuilder->expects($this->once())
            ->method('build')
            ->with(self::BUNDLES, ['Storefront' => true])
            ->willReturn(['SwagPayPal' => 0, 'MyTheme' => 0, 'Storefront' => 0]);

        $resolver = $this->createResolver(
            new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_API)]),
            $this->themeRepository([new ThemeCollection()]),
        );

        static::assertSame(['SwagPayPal', 'MyTheme', 'Storefront'], $resolver->resolveForDocuments(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
    }

    public function testDocumentsUseTheSalesChannelTheme(): void
    {
        $this->themeInheritanceBuilder->expects($this->once())
            ->method('build')
            ->with(self::BUNDLES, ['MyTheme' => true, 'Storefront' => true])
            ->willReturn(self::BUNDLES);

        $resolver = $this->createResolver(
            new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_STOREFRONT)]),
            $this->themeRepository([new ThemeCollection([$this->theme(technicalName: 'MyTheme')])]),
        );

        $resolver->resolveForDocuments(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());
    }

    public function testDocumentsKeepTheBundleOrderWithoutTheStorefrontBundle(): void
    {
        $resolver = new NamespaceHierarchyResolver(
            $this->salesChannelRepository(new SalesChannelCollection([$this->salesChannel(Defaults::SALES_CHANNEL_TYPE_API)])),
            null,
            $this->bundleHierarchyBuilder,
            null,
        );

        static::assertSame(['MyTheme', 'SwagPayPal', 'Storefront'], $resolver->resolveForDocuments(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()));
    }

    public function testDocumentsThrowForUnknownSalesChannels(): void
    {
        $resolver = $this->createResolver(new SalesChannelCollection(), $this->themeRepository([]));

        $this->expectExceptionObject(SalesChannelException::salesChannelNotFound(TestDefaults::SALES_CHANNEL));

        $resolver->resolveForDocuments(TestDefaults::SALES_CHANNEL, Context::createDefaultContext());
    }

    /**
     * @param StaticEntityRepository<ThemeCollection> $themeRepository
     */
    private function createResolver(SalesChannelCollection $salesChannels, StaticEntityRepository $themeRepository): NamespaceHierarchyResolver
    {
        return new NamespaceHierarchyResolver(
            $this->salesChannelRepository($salesChannels),
            $themeRepository,
            $this->bundleHierarchyBuilder,
            $this->themeInheritanceBuilder,
        );
    }

    /**
     * @return StaticEntityRepository<SalesChannelCollection>
     */
    private function salesChannelRepository(SalesChannelCollection ...$searches): StaticEntityRepository
    {
        /** @var StaticEntityRepository<SalesChannelCollection> $repository */
        $repository = new StaticEntityRepository(\array_values($searches));

        return $repository;
    }

    /**
     * @param list<ThemeCollection> $searches
     * @param list<Criteria> $searchedCriteria
     *
     * @return StaticEntityRepository<ThemeCollection>
     */
    private function themeRepository(array $searches, array &$searchedCriteria = []): StaticEntityRepository
    {
        /** @var StaticEntityRepository<ThemeCollection> $repository */
        $repository = new StaticEntityRepository(\array_map(
            static function (ThemeCollection $themes) use (&$searchedCriteria): callable {
                return static function (Criteria $criteria) use ($themes, &$searchedCriteria): ThemeCollection {
                    $searchedCriteria[] = $criteria;

                    return $themes;
                };
            },
            $searches,
        ));

        return $repository;
    }

    private function salesChannel(string $typeId): SalesChannelEntity
    {
        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId(TestDefaults::SALES_CHANNEL);
        $salesChannel->setTypeId($typeId);

        return $salesChannel;
    }

    private function theme(?string $technicalName = null, ?string $parentThemeId = null): ThemeEntity
    {
        $theme = new ThemeEntity();
        $theme->setId(Uuid::randomHex());
        $theme->setTechnicalName($technicalName);
        $theme->setParentThemeId($parentThemeId);

        return $theme;
    }
}
