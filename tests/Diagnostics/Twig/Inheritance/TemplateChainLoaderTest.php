<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Inheritance;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChain;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Source;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TemplateChainLoader::class)]
class TemplateChainLoaderTest extends TestCase
{
    private const CONFIRM_PAGE = 'storefront/page/checkout/confirm/index.html.twig';
    private const PLUGIN_DIR = __DIR__ . '/../../../..';

    private Filesystem $filesystem;

    private string $link;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->link = \sys_get_temp_dir() . '/swag-paypal-diagnostics-' . \bin2hex(\random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->link);
    }

    public function testSplitsTheChainAroundThePluginTemplate(): void
    {
        $loader = new TemplateChainLoader(new ArrayLoader([
            '@MyTheme/' . self::CONFIRM_PAGE => 'theme',
            '@SwagPayPal/' . self::CONFIRM_PAGE => 'paypal',
            '@Storefront/' . self::CONFIRM_PAGE => 'storefront',
        ]), '/var/www/shop');

        $chains = $loader->load(['MyTheme', 'OtherPlugin', 'SwagPayPal', 'Storefront'], TemplateArea::Storefront);

        static::assertCount(1, $chains);
        static::assertSame(self::CONFIRM_PAGE, $chains[0]->templatePath);
        static::assertSame(['@MyTheme/' . self::CONFIRM_PAGE], $this->names($chains[0]->above));
        static::assertSame('@SwagPayPal/' . self::CONFIRM_PAGE, $chains[0]->plugin->getName());
        static::assertSame(['@Storefront/' . self::CONFIRM_PAGE], $this->names($chains[0]->below));
    }

    public function testSkipsPluginTemplatesNothingComesAfter(): void
    {
        $loader = new TemplateChainLoader(new ArrayLoader([
            '@SwagPayPal/' . self::CONFIRM_PAGE => 'paypal',
            '@Storefront/' . self::CONFIRM_PAGE => 'storefront',
        ]), '/var/www/shop');

        static::assertSame([], $loader->load(['MyTheme', 'SwagPayPal', 'Storefront'], TemplateArea::Storefront));
    }

    public function testLoadsEveryStorefrontTemplateOfThePlugin(): void
    {
        $loader = new TemplateChainLoader($this->filesystemLoader(self::PLUGIN_DIR), self::PLUGIN_DIR);

        $templatePaths = \array_map(static fn (TemplateChain $chain) => $chain->templatePath, $loader->load(['MyTheme', 'SwagPayPal'], TemplateArea::Storefront));

        static::assertContains(self::CONFIRM_PAGE, $templatePaths);
        static::assertContains('storefront/paypal/checkout-button.html.twig', $templatePaths);
        static::assertSame([], \array_filter($templatePaths, static fn (string $path) => !\str_starts_with($path, 'storefront/')));
    }

    public function testLoadsTheDocumentTemplatesOfThePlugin(): void
    {
        $loader = new TemplateChainLoader(new ArrayLoader([
            '@MyTheme/documents/invoice.html.twig' => 'theme',
            '@SwagPayPal/documents/invoice.html.twig' => 'paypal',
            '@Framework/documents/invoice.html.twig' => 'framework',
            '@MyTheme/' . self::CONFIRM_PAGE => 'theme',
            '@SwagPayPal/' . self::CONFIRM_PAGE => 'paypal',
        ]), '/var/www/shop');

        $chains = $loader->load(['MyTheme', 'SwagPayPal', 'Framework'], TemplateArea::Documents);

        static::assertCount(1, $chains);
        static::assertSame('documents/invoice.html.twig', $chains[0]->templatePath);
        static::assertSame(['@Framework/documents/invoice.html.twig'], $this->names($chains[0]->below));
    }

    public function testMakesTemplatePathsRelativeToTheProject(): void
    {
        $loader = new TemplateChainLoader($this->filesystemLoader(self::PLUGIN_DIR), self::PLUGIN_DIR);

        static::assertSame('src/Resources/views/' . self::CONFIRM_PAGE, $this->confirmPageChain($loader)->plugin->getPath());
    }

    public function testMakesTemplatePathsRelativeToALinkedProjectDirectory(): void
    {
        // Deployments often link the project directory, while Twig returns the resolved template paths
        $this->filesystem->symlink((string) \realpath(self::PLUGIN_DIR), $this->link);
        $loader = new TemplateChainLoader($this->filesystemLoader($this->link), $this->link);

        static::assertSame('src/Resources/views/' . self::CONFIRM_PAGE, $this->confirmPageChain($loader)->plugin->getPath());
    }

    public function testKeepsAbsolutePathsOfTemplatesOutsideTheProject(): void
    {
        $loader = new TemplateChainLoader($this->filesystemLoader(self::PLUGIN_DIR), '/var/www/shop');

        static::assertSame(
            \realpath(self::PLUGIN_DIR . '/src/Resources/views/' . self::CONFIRM_PAGE),
            $this->confirmPageChain($loader)->plugin->getPath(),
        );
    }

    private function confirmPageChain(TemplateChainLoader $loader): TemplateChain
    {
        foreach ($loader->load(['MyTheme', 'SwagPayPal'], TemplateArea::Storefront) as $chain) {
            if ($chain->templatePath === self::CONFIRM_PAGE) {
                return $chain;
            }
        }

        static::fail('No chain for the confirm page');
    }

    /**
     * The plugin's own templates under a second namespace, so that something comes after each of them
     */
    private function filesystemLoader(string $pluginDir): FilesystemLoader
    {
        $loader = new FilesystemLoader();
        $loader->addPath($pluginDir . '/src/Resources/views', 'MyTheme');
        $loader->addPath($pluginDir . '/src/Resources/views', 'SwagPayPal');

        return $loader;
    }

    /**
     * @param list<Source> $sources
     *
     * @return list<string>
     */
    private function names(array $sources): array
    {
        return \array_map(static fn (Source $source) => $source->getName(), $sources);
    }
}
