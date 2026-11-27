<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Diagnostics\Twig\Inheritance\NamespaceHierarchyResolver;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChain;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverride;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideAnalyzer;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideType;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Swag\PayPal\Diagnostics\Twig\TemplateAreaResult;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnostics;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnosticsResult;
use Twig\Source;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TwigDiagnostics::class)]
class TwigDiagnosticsTest extends TestCase
{
    private NamespaceHierarchyResolver&MockObject $namespaceHierarchyResolver;

    private TemplateChainLoader&MockObject $templateChainLoader;

    private TemplateOverrideAnalyzer&MockObject $analyzer;

    private TwigDiagnostics $twigDiagnostics;

    protected function setUp(): void
    {
        $this->namespaceHierarchyResolver = $this->createMock(NamespaceHierarchyResolver::class);
        $this->templateChainLoader = $this->createMock(TemplateChainLoader::class);
        $this->analyzer = $this->createMock(TemplateOverrideAnalyzer::class);

        $this->twigDiagnostics = new TwigDiagnostics($this->namespaceHierarchyResolver, $this->templateChainLoader, $this->analyzer, [
            'Subscription' => ['path' => '/plugins/SwagCommercial/src/Subscription', 'namespace' => 'Shopware\\Commercial\\Subscription'],
            'CommercialAddons' => ['path' => '/plugins/CommercialAddons/src', 'namespace' => 'Shopware\\CommercialAddons'],
            'MyTheme' => ['path' => '/plugins/MyTheme/src', 'namespace' => 'Acme\\MyTheme'],
        ]);
    }

    public function testChecksTheTemplatesOfBothAreas(): void
    {
        $footer = $this->chain('storefront/layout/footer/footer.html.twig');
        $base = $this->chain('storefront/base.html.twig');
        $invoice = $this->chain('documents/invoice.html.twig');
        $footerOverride = $this->override('storefront/layout/footer/footer.html.twig');
        $baseOverride = $this->override('storefront/base.html.twig');
        $invoiceOverride = $this->override('documents/invoice.html.twig');

        $this->namespaceHierarchyResolver->method('resolveForStorefront')->willReturn(['MyTheme', 'SwagPayPal', 'Storefront']);
        $this->namespaceHierarchyResolver->method('resolveForDocuments')->willReturn(['MyTheme', 'SwagPayPal', 'Framework']);
        $this->templateChainLoader->method('load')->willReturnMap([
            [['MyTheme', 'SwagPayPal', 'Storefront'], TemplateArea::Storefront, [$footer, $base]],
            [['MyTheme', 'SwagPayPal', 'Framework'], TemplateArea::Documents, [$invoice]],
        ]);
        $this->analyzer->method('analyze')->willReturnMap([
            [$footer, [$footerOverride]],
            [$base, [$baseOverride]],
            [$invoice, [$invoiceOverride]],
        ]);

        static::assertEquals(
            new TwigDiagnosticsResult(
                storefront: new TemplateAreaResult(applicable: true, overrides: [$footerOverride, $baseOverride]),
                documents: new TemplateAreaResult(applicable: true, overrides: [$invoiceOverride]),
            ),
            $this->twigDiagnostics->check(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()),
        );
    }

    public function testChecksDocumentsOfSalesChannelsWithoutStorefront(): void
    {
        $invoice = $this->chain('documents/invoice.html.twig');
        $invoiceOverride = $this->override('documents/invoice.html.twig');

        $this->namespaceHierarchyResolver->method('resolveForStorefront')->willReturn(null);
        $this->namespaceHierarchyResolver->method('resolveForDocuments')->willReturn(['MyTheme', 'SwagPayPal', 'Framework']);
        $this->templateChainLoader->expects($this->once())
            ->method('load')
            ->with(['MyTheme', 'SwagPayPal', 'Framework'], TemplateArea::Documents)
            ->willReturn([$invoice]);
        $this->analyzer->method('analyze')->with($invoice)->willReturn([$invoiceOverride]);

        static::assertEquals(
            new TwigDiagnosticsResult(
                storefront: new TemplateAreaResult(applicable: false, overrides: []),
                documents: new TemplateAreaResult(applicable: true, overrides: [$invoiceOverride]),
            ),
            $this->twigDiagnostics->check(TestDefaults::SALES_CHANNEL, Context::createDefaultContext()),
        );
    }

    public function testIgnoresOverridesOfShopwaresExtensions(): void
    {
        $invoice = $this->chain('documents/invoice.html.twig');
        $commercialOverride = $this->override('documents/invoice.html.twig', 'Subscription');
        $appOverride = $this->override('documents/invoice.html.twig', 'ShopwarePayments');
        $themeOverride = $this->override('documents/invoice.html.twig', 'MyTheme');
        // Only shares the beginning of the PHP namespace with SwagCommercial
        $lookalikeOverride = $this->override('documents/invoice.html.twig', 'CommercialAddons');

        $this->namespaceHierarchyResolver->method('resolveForStorefront')->willReturn(null);
        $this->namespaceHierarchyResolver->method('resolveForDocuments')->willReturn(['MyTheme', 'Subscription', 'SwagPayPal', 'Framework']);
        $this->templateChainLoader->method('load')->willReturn([$invoice]);
        $this->analyzer->method('analyze')->with($invoice)->willReturn([$commercialOverride, $appOverride, $themeOverride, $lookalikeOverride]);

        static::assertEquals(
            new TemplateAreaResult(applicable: true, overrides: [$themeOverride, $lookalikeOverride]),
            $this->twigDiagnostics->check(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())->documents,
        );
    }

    public function testSortsTheOverridesMostSevereFirst(): void
    {
        $invoice = $this->chain('documents/invoice.html.twig');
        $unreadable = $this->override('documents/a.html.twig', type: TemplateOverrideType::Unreadable);
        $conditional = $this->override('documents/b.html.twig', type: TemplateOverrideType::ConditionalParent);
        $replaced = $this->override('documents/c.html.twig', type: TemplateOverrideType::TemplateReplaced);
        $skipped = $this->override('documents/d.html.twig', type: TemplateOverrideType::PluginTemplateSkipped);
        $dynamic = $this->override('documents/e.html.twig', type: TemplateOverrideType::DynamicExtends);

        $this->namespaceHierarchyResolver->method('resolveForStorefront')->willReturn(null);
        $this->namespaceHierarchyResolver->method('resolveForDocuments')->willReturn(['MyTheme', 'SwagPayPal', 'Framework']);
        $this->templateChainLoader->method('load')->willReturn([$invoice]);
        $this->analyzer->method('analyze')->with($invoice)->willReturn([$unreadable, $conditional, $replaced, $dynamic, $skipped]);

        static::assertEquals(
            [$replaced, $skipped, $conditional, $dynamic, $unreadable],
            $this->twigDiagnostics->check(TestDefaults::SALES_CHANNEL, Context::createDefaultContext())->documents->overrides,
        );
    }

    private function chain(string $templatePath): TemplateChain
    {
        return new TemplateChain(
            $templatePath,
            [new Source('', '@MyTheme/' . $templatePath)],
            new Source('', '@SwagPayPal/' . $templatePath),
            [],
        );
    }

    private function override(string $templatePath, string $namespace = 'MyTheme', TemplateOverrideType $type = TemplateOverrideType::TemplateReplaced): TemplateOverride
    {
        return new TemplateOverride(
            type: $type,
            templatePath: $templatePath,
            namespace: $namespace,
            file: null,
            line: 0,
            block: null,
            affectedBlocks: [],
        );
    }
}
