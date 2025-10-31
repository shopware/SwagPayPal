<?php declare(strict_types=1);

/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


// SETUP

$_SERVER['CI'] ??= false;
$pluginRootPath = dirname(__DIR__);

require $pluginRootPath . '/tests/PHPStanBootstrap.php';

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
        'symfony' => ['containerXmlPath' => \sprintf('%s/%s%sDebugContainer.xml', $kernel->getCacheDir(), str_replace('\\', '_', $kernel::class), \ucfirst($kernel->getEnvironment()))],
        'reportUnmatchedIgnoredErrors' => !((bool) $_SERVER['CI']),
    ],
];

$encoded = \json_encode($phpstanConfig, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/../phpstan.dynamic.neon', $encoded);

if ((bool) $_SERVER['CI']) { // Print config for clearity in workflow
    echo 'Generated config:' . \PHP_EOL . $encoded . \PHP_EOL;
}
