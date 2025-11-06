<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

$_SERVER['PROJECT_ROOT'] ??= dirname(__DIR__, 4);

function getPluginPath(string $name): ?string {
    foreach (glob($_SERVER['PROJECT_ROOT'] . '/custom/*plugins/*', \GLOB_ONLYDIR) ?: [] as $pluginDir) {
        if (is_file($pluginDir . '/composer.json') && is_file($pluginDir . '/src/' . $name . '.php')) {
            return $pluginDir;
        }
    }

    return null;
};

$bootstrapper = (new TestBootstrapper())->setProjectDir($_SERVER['PROJECT_ROOT']);

$plugins = ['SwagPayPal'];
foreach (['SwagCmsExtensions', 'SwagCommercial'] as $pluginName) {
    if (getPluginPath($pluginName)) {
        $plugins[] = $pluginName;

        echo "{$pluginName} detected, require being active." . \PHP_EOL;
    }
}

return $bootstrapper
    ->setLoadEnvFile(true)
    ->addActivePlugins(...$plugins)
    ->bootstrap()
    ->getClassLoader();
