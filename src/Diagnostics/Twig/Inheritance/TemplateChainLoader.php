<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Inheritance;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Symfony\Component\Finder\Finder;
use Twig\Loader\LoaderInterface;
use Twig\Source;

/**
 * Paths are relative to the project directory, absolute ones would reveal the server's directory layout.
 * Compared resolved, as Twig resolves symlinks and deployments often link the project root.
 *
 * @internal
 */
#[Package('checkout')]
class TemplateChainLoader
{
    public const PLUGIN_NAMESPACE = 'SwagPayPal';

    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly string $projectDir,
        private readonly string $viewsDirectory = __DIR__ . '/../../../Resources/views',
    ) {
    }

    /**
     * @param list<string> $namespaceHierarchy
     *
     * @return list<TemplateChain>
     */
    public function load(array $namespaceHierarchy, TemplateArea $area): array
    {
        $chains = [];
        foreach ($this->getPluginTemplatePaths($area) as $templatePath) {
            $above = [];
            $plugin = null;
            $below = [];

            foreach ($namespaceHierarchy as $namespace) {
                $name = '@' . $namespace . '/' . $templatePath;
                if (!$this->loader->exists($name)) {
                    continue;
                }

                $source = $this->withRelativePath($this->loader->getSourceContext($name));

                if ($namespace === self::PLUGIN_NAMESPACE) {
                    $plugin = $source;
                } elseif ($plugin === null) {
                    $above[] = $source;
                } else {
                    $below[] = $source;
                }
            }

            if ($plugin !== null && $above !== []) {
                $chains[] = new TemplateChain($templatePath, $above, $plugin, $below);
            }
        }

        return $chains;
    }

    /**
     * @return list<string>
     */
    private function getPluginTemplatePaths(TemplateArea $area): array
    {
        $directory = $this->viewsDirectory . '/' . $area->value;
        if (!\is_dir($directory)) {
            return [];
        }

        $files = (new Finder())
            ->files()
            ->in($directory)
            ->name('*.twig')
            ->sortByName();

        $paths = [];
        foreach ($files as $file) {
            $paths[] = $area->value . '/' . \str_replace('\\', '/', $file->getRelativePathname());
        }

        return $paths;
    }

    /**
     * Templates outside the project, e.g. of a linked plugin, keep their absolute path
     */
    private function withRelativePath(Source $source): Source
    {
        $path = $source->getPath();

        foreach (\array_unique([\realpath($this->projectDir) ?: $this->projectDir, $this->projectDir]) as $projectDir) {
            $prefix = \rtrim($projectDir, '/') . '/';

            if (\str_starts_with($path, $prefix)) {
                return new Source($source->getCode(), $source->getName(), \mb_substr($path, \mb_strlen($prefix)));
            }
        }

        return $source;
    }
}
