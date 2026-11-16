<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\Framework\Adapter\Twig\NamespaceHierarchy\BundleHierarchyBuilder;
use Shopware\Storefront\Theme\Twig\ThemeInheritanceBuilderInterface;
use Swag\PayPal\Diagnostics\Administration\TwigDiagnosticsController;
use Swag\PayPal\Diagnostics\Reachability\HostResolver;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityProbe;
use Swag\PayPal\Diagnostics\Twig\Inheritance\NamespaceHierarchyResolver;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideAnalyzer;
use Swag\PayPal\Diagnostics\Twig\Parser\TwigTemplateParser;
use Swag\PayPal\Diagnostics\Twig\TwigDiagnostics;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    // Reachability
    $services->set(HostResolver::class);

    $services->set(ReachabilityProbe::class)
        ->args([
            service(HttpClientInterface::class),
            service(HostResolver::class),
        ]);

    // Twig
    $services->set(TwigDiagnosticsController::class)
        ->public()
        ->args([
            service(TwigDiagnostics::class),
        ])
        ->call('setContainer', [service('service_container')]);

    $services->set(TwigDiagnostics::class)
        ->args([
            service(NamespaceHierarchyResolver::class),
            service(TemplateChainLoader::class),
            service(TemplateOverrideAnalyzer::class),
            param('kernel.bundles_metadata'),
        ]);

    $services->set(NamespaceHierarchyResolver::class)
        ->args([
            service('sales_channel.repository'),
            service('theme.repository')->nullOnInvalid(),
            service(BundleHierarchyBuilder::class),
            service(ThemeInheritanceBuilderInterface::class)->nullOnInvalid(),
        ]);

    $services->set(TemplateChainLoader::class)
        ->args([
            service('twig.loader'),
            param('kernel.project_dir'),
        ]);

    $services->set(TwigTemplateParser::class)
        ->args([
            service('twig'),
        ]);

    $services->set(TemplateOverrideAnalyzer::class)
        ->args([
            service(TwigTemplateParser::class),
        ]);
};
