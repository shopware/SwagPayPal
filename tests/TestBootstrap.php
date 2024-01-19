<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

if (is_readable(__DIR__ . '/../vendor/shopware/platform/src/Core/TestBootstrapper.php')) {
    require __DIR__ . '/../vendor/shopware/platform/src/Core/TestBootstrapper.php';
} elseif (is_readable(__DIR__ . '/../vendor/shopware/core/TestBootstrapper.php')) {
    require __DIR__ . '/../vendor/shopware/core/TestBootstrapper.php';
} else {
    require __DIR__ . '/../../../../src/Core/TestBootstrapper.php';
}

return (new TestBootstrapper())
    ->setProjectDir($_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4))
    ->setLoadEnvFile(true)
    ->setForceInstallPlugins(true)
    ->addActivePlugins('SwagCmsExtensions')
    ->addCallingPlugin()
    ->bootstrap()
    ->getClassLoader();
