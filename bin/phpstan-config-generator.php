<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Composer\InstalledVersions;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\StaticKernelPluginLoader;
use Shopware\Development\Kernel;
use Swag\PayPal\SwagPayPal;
use Symfony\Component\Dotenv\Dotenv;

$classLoader = require __DIR__ . '/../../../../vendor/autoload.php';
(new Dotenv())->usePutEnv()->load(__DIR__ . '/../../../../.env');

$shopwareVersion = InstalledVersions::getVersion('shopware/platform') . '@' . InstalledVersions::getReference('shopware/platform');

$pluginRootPath = \dirname(__DIR__);
$composerJson = \json_decode((string) \file_get_contents($pluginRootPath . '/composer.json'), true);

$swagPayPal = [
    'autoload' => $composerJson['autoload'],
    'baseClass' => SwagPayPal::class,
    'managedByComposer' => false,
    'name' => 'SwagPayPal',
    'version' => $composerJson['version'],
    'active' => true,
    'path' => $pluginRootPath,
];
$pluginLoader = new StaticKernelPluginLoader($classLoader, null, [$swagPayPal]);

$kernel = new Kernel('dev', true, $pluginLoader, $shopwareVersion);
$kernel->boot();
$projectDir = $kernel->getProjectDir();
$cacheDir = $kernel->getCacheDir();

$relativeCacheDir = \str_replace($projectDir, '', $cacheDir);

$phpStanConfigDist = \file_get_contents(__DIR__ . '/../phpstan.neon.dist');
if ($phpStanConfigDist === false) {
    throw new RuntimeException('phpstan.neon.dist file not found');
}

// because the cache dir is hashed by Shopware, we need to set the PHPStan config dynamically
$phpStanConfig = \str_replace(
    [
        "\n        # the placeholder \"%ShopwareHashedCacheDir%\" will be replaced on execution by bin/phpstan-config-generator.php script",
        '%ShopwareHashedCacheDir%',
    ],
    [
        '',
        $relativeCacheDir,
    ],
    $phpStanConfigDist
);

\file_put_contents(__DIR__ . '/../phpstan.neon', $phpStanConfig);
