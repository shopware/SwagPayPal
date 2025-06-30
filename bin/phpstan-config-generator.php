<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\DevOps\StaticAnalyze\StaticAnalyzeKernel;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Swag\PayPal\SwagPayPal;

// SETUP

$_SERVER['CI'] ??= false;
$projectRoot = $_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4);
$pluginRootPath = dirname(__DIR__);

$classLoader = require $projectRoot . '/vendor/autoload.php';

/** @var array{autoload: array{}, version: string} $composer */
$composer = json_decode((string) file_get_contents($pluginRootPath . '/composer.json'), true);

$pluginLoader = new StaticKernelPluginLoader($classLoader, null, [[
    'name' => 'SwagPayPal',
    'active' => true,
    'version' => $composer['version'],
    'baseClass' => SwagPayPal::class,
    'managedByComposer' => true,
    'autoload' => $composer['autoload'],
    'path' => $pluginRootPath,
]]);

KernelFactory::$kernelClass = StaticAnalyzeKernel::class;

/** @var StaticAnalyzeKernel $kernel */
$kernel = KernelFactory::create('dev', true, $classLoader, $pluginLoader);
$kernel->boot();

// GENERATE CONFIG

$shopwareVersion = $kernel->getContainer()->getParameter('kernel.shopware_version');
echo \sprintf('Identified shopware version "%s"' . \PHP_EOL, $shopwareVersion);

$versionedConfig = \sprintf('%s/phpstan-%s.neon.dist', $pluginRootPath, $shopwareVersion);

$phpstanConfig = [
    'includes' => \array_merge(
        [$kernel->getProjectDir() . '/src/Core/DevOps/StaticAnalyze/PHPStan/common.neon'],
        \file_exists($versionedConfig) ? [$versionedConfig] : [],
    ),
    'parameters' => [
        'symfony' => ['containerXmlPath' => \sprintf('%s/%sDevDebugContainer.xml', $kernel->getCacheDir(), str_replace('\\', '_', $kernel::class))],
        'reportUnmatchedIgnoredErrors' => !((bool) $_SERVER['CI']),
    ],
];

$encoded = \json_encode($phpstanConfig, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/../phpstan.dynamic.neon', $encoded);

if ((bool) $_SERVER['CI']) { // Print config for clearity in workflow
    echo 'Generated config:' . \PHP_EOL . $encoded . \PHP_EOL;
}
