<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Inheritance;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Diagnostics\Twig\Inheritance\NamespaceHierarchyResolver;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChain;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Twig\Source;

/**
 * Checks the namespace hierarchy against Shopware's real bundle and theme order
 *
 * @internal
 */
#[Package('checkout')]
class TemplateInheritanceTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use KernelTestBehaviour;

    private const CONFIRM_PAGE = 'storefront/page/checkout/confirm/index.html.twig';

    private Context $context;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();
    }

    public function testTheStorefrontThemeRendersStorefrontTemplatesLast(): void
    {
        $this->givenStorefrontSalesChannel($this->getStorefrontThemeId());

        $hierarchy = $this->resolve();

        static::assertSame(['Storefront'], \array_slice($hierarchy, -1));
        static::assertLessThan(\array_search('Storefront', $hierarchy, true), \array_search('SwagPayPal', $hierarchy, true));
    }

    public function testThemeCopyRendersLikeTheThemeItWasCopiedFrom(): void
    {
        $copyId = Uuid::randomHex();
        $this->getThemeRepository()->create([[
            'id' => $copyId,
            'name' => 'Copy of Storefront',
            'author' => 'Merchant',
            'parentThemeId' => $this->getStorefrontThemeId(),
        ]], $this->context);
        $this->givenStorefrontSalesChannel($copyId);

        static::assertSame(['Storefront'], \array_slice($this->resolve(), -1));
    }

    public function testLoadsThePluginTemplatesThroughTheShopsTwigLoader(): void
    {
        // Storefront comes after the plugin here, so that its templates count as overriding the plugin's
        $chains = static::getContainer()->get(TemplateChainLoader::class)->load(['Storefront', 'SwagPayPal'], TemplateArea::Storefront);

        $confirmPage = \array_values(\array_filter($chains, static fn (TemplateChain $chain) => $chain->templatePath === self::CONFIRM_PAGE));
        static::assertCount(1, $confirmPage);
        static::assertSame('@Storefront/' . self::CONFIRM_PAGE, $confirmPage[0]->above[0]->getName());
        static::assertStringEndsWith('src/Resources/views/' . self::CONFIRM_PAGE, $confirmPage[0]->plugin->getPath());
        static::assertStringStartsNotWith('/', $confirmPage[0]->plugin->getPath());
    }

    public function testDocumentsOfAHeadlessSalesChannelUseTheStorefrontTheme(): void
    {
        $hierarchy = static::getContainer()->get(NamespaceHierarchyResolver::class)->resolveForDocuments(TestDefaults::SALES_CHANNEL, $this->context);

        // Without the theme, the bundle order would end with another bundle
        static::assertSame(['Storefront'], \array_slice($hierarchy, -1));
    }

    public function testLoadsTheInvoiceChainThroughTheShopsTwigLoader(): void
    {
        $hierarchy = static::getContainer()->get(NamespaceHierarchyResolver::class)->resolveForDocuments(TestDefaults::SALES_CHANNEL, $this->context);
        // The Framework template comes after the plugin here, so that the chain is loaded
        $hierarchy = [...\array_values(\array_diff($hierarchy, ['SwagPayPal'])), 'SwagPayPal'];

        $chains = static::getContainer()->get(TemplateChainLoader::class)->load($hierarchy, TemplateArea::Documents);

        $invoice = \array_values(\array_filter($chains, static fn (TemplateChain $chain) => $chain->templatePath === 'documents/invoice.html.twig'));
        static::assertCount(1, $invoice);
        static::assertContains('@Framework/documents/invoice.html.twig', \array_map(static fn (Source $source) => $source->getName(), $invoice[0]->above));
    }

    /**
     * @return list<string>
     */
    private function resolve(): array
    {
        $hierarchy = static::getContainer()->get(NamespaceHierarchyResolver::class)->resolveForStorefront(TestDefaults::SALES_CHANNEL, $this->context);
        static::assertIsArray($hierarchy);

        return $hierarchy;
    }

    private function givenStorefrontSalesChannel(string $themeId): void
    {
        static::getContainer()->get('sales_channel.repository')->update([[
            'id' => TestDefaults::SALES_CHANNEL,
            'typeId' => Defaults::SALES_CHANNEL_TYPE_STOREFRONT,
        ]], $this->context);

        $this->getThemeRepository()->update([[
            'id' => $themeId,
            'salesChannels' => [['id' => TestDefaults::SALES_CHANNEL]],
        ]], $this->context);
    }

    private function getStorefrontThemeId(): string
    {
        $themeId = $this->getThemeRepository()->searchIds(
            (new Criteria())->addFilter(new EqualsFilter('technicalName', 'Storefront')),
            $this->context,
        )->firstId();
        static::assertIsString($themeId);

        return $themeId;
    }

    private function getThemeRepository(): EntityRepository
    {
        $repository = static::getContainer()->get('theme.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }
}
