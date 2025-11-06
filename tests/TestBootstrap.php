<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

$bootstrapper = (new TestBootstrapper())->setProjectDir($_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4));

$plugins = ['SwagPayPal'];
foreach (['SwagCmsExtensions', 'SwagCommercial'] as $pluginName) {
    if (\is_readable($bootstrapper->getProjectDir() . '/custom/plugins/' . $name . '/composer.json')) {
        $plugins[] = $pluginName;

        echo "{$pluginName} detected, require being active." . \PHP_EOL;
    }
}

return $bootstrapper
    ->setLoadEnvFile(true)
    ->addActivePlugins(...$plugins)
    ->bootstrap()
    ->getClassLoader();
