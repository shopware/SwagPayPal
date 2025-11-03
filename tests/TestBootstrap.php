<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

$projectDir = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4);

return (new TestBootstrapper())
    ->setProjectDir($projectDir)
    ->setLoadEnvFile(true)
    ->setForceInstallPlugins(true)
    ->addActivePlugins('SwagCmsExtensions')
    ->addCallingPlugin()
    ->bootstrap()
    ->setClassLoader(require $projectDir . '/vendor/autoload.php')
    ->getClassLoader();
