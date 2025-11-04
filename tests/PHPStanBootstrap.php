<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\DevOps\StaticAnalyze\StaticAnalyzeKernel;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Core\TestBootstrapper;

$projectRoot = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4);

if (!\class_exists(TestBootstrapper::class)) {
    require_once ($projectRoot) . '/vendor/autoload.php';
}

$bootstrapper = (new TestBootstrapper())->setProjectDir($projectRoot);

$plugins = ['SwagPayPal'];
foreach (['SwagCmsExtensions', 'SwagCommercial'] as $pluginName) {
    if ($bootstrapper->getPluginPath($pluginName)) {
        $plugins[] = $pluginName;

        echo "{$pluginName} detected, require being active." . \PHP_EOL;
    }
}

$bootsrapper = $bootstrapper
    ->setLoadEnvFile(true)
    ->addActivePlugins(...$plugins);

$pluginLoader = new StaticKernelPluginLoader($bootsrapper->getClassLoader(), plugins: \array_map(
    function (string $plugin) use ($bootsrapper) {
        /** @var array{autoload: array{}, version: string, extra: array{}} $composer */
        $composer = \json_decode(\file_get_contents($bootsrapper->getPluginPath($plugin) . '/composer.json') ?: '', true, flags: \JSON_THROW_ON_ERROR);

        return [
            'name' => $plugin,
            'active' => true,
            'version' => $composer['version'],
            'baseClass' => $composer['extra']['shopware-plugin-class'] ?? '',
            'managedByComposer' => false, // even though some are, namespaces wouldn't load if set true
            'autoload' => $composer['autoload'],
            'path' => $bootsrapper->getPluginPath($plugin),
        ];
    },
    $plugins,
));

KernelFactory::$kernelClass = StaticAnalyzeKernel::class;
/** @phpstan-ignore varTag.internalClass */ /** @var StaticAnalyzeKernel $kernel */
$kernel = KernelFactory::create(
    environment: 'phpstan_dev',
    debug: true,
    classLoader: $bootsrapper->getClassLoader(),
    pluginLoader: $pluginLoader,
);

$kernel->boot();

return $kernel;
