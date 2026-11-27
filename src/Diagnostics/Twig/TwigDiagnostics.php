<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Twig\Inheritance\NamespaceHierarchyResolver;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverride;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideAnalyzer;

/**
 * @internal
 */
#[Package('checkout')]
class TwigDiagnostics
{
    // SwagCommercial registers one bundle, and so one Twig namespace, per module below this PHP namespace
    private const SHOPWARE_BUNDLE_NAMESPACES = ['Shopware\\Commercial\\'];

    // Apps aren't bundles, their name is their Twig namespace
    private const SHOPWARE_APPS = ['ShopwarePayments', 'SwagBraintreeApp'];

    /**
     * @param array<string, array{path: string, namespace: string}> $bundlesMetadata
     */
    public function __construct(
        private readonly NamespaceHierarchyResolver $namespaceHierarchyResolver,
        private readonly TemplateChainLoader $templateChainLoader,
        private readonly TemplateOverrideAnalyzer $analyzer,
        private readonly array $bundlesMetadata,
    ) {
    }

    public function check(string $salesChannelId, Context $context): TwigDiagnosticsResult
    {
        return new TwigDiagnosticsResult(
            storefront: $this->checkArea(TemplateArea::Storefront, $this->namespaceHierarchyResolver->resolveForStorefront($salesChannelId, $context)),
            documents: $this->checkArea(TemplateArea::Documents, $this->namespaceHierarchyResolver->resolveForDocuments($salesChannelId, $context)),
        );
    }

    /**
     * @param list<string>|null $namespaceHierarchy
     */
    private function checkArea(TemplateArea $area, ?array $namespaceHierarchy): TemplateAreaResult
    {
        if ($namespaceHierarchy === null) {
            return new TemplateAreaResult(applicable: false, overrides: []);
        }

        $overrides = [];
        foreach ($this->templateChainLoader->load($namespaceHierarchy, $area) as $chain) {
            \array_push($overrides, ...$this->analyzer->analyze($chain));
        }

        $overrides = \array_values(\array_filter(
            $overrides,
            fn (TemplateOverride $override) => !$this->isMadeByShopware($override->namespace),
        ));
        \usort($overrides, static fn (TemplateOverride $a, TemplateOverride $b) => $a->severity->compare($b->severity));

        return new TemplateAreaResult(applicable: true, overrides: $overrides);
    }

    private function isMadeByShopware(string $namespace): bool
    {
        if (\in_array($namespace, self::SHOPWARE_APPS, true)) {
            return true;
        }

        $phpNamespace = $this->bundlesMetadata[$namespace]['namespace'] ?? null;
        if ($phpNamespace === null) {
            return false;
        }

        foreach (self::SHOPWARE_BUNDLE_NAMESPACES as $prefix) {
            if (\str_starts_with($phpNamespace . '\\', $prefix)) {
                return true;
            }
        }

        return false;
    }
}
