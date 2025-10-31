<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

$plugins = ['SwagPayPal'];

foreach (['SwagCmsExtensions', 'SwagCommercial'] as $pluginName) {
    if ((new TestBootstrapper())->getPluginPath($pluginName)) {
        $plugins[] = $pluginName;

        echo "{$pluginName} detected, require being active." . \PHP_EOL;
    }
}

return (new TestBootstrapper())
    ->setProjectDir($_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4))
    ->setLoadEnvFile(true)
    ->addActivePlugins(...$plugins)
    ->bootstrap()
    ->getClassLoader();
