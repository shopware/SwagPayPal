<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

$optionalPlugins = [
    'SwagCmsExtensions' => 'src/SwagCmsExtensions.php',
    'SwagCommercial' => 'src/Subscription/Subscription.php',
];

$pluginDirectory = dirname(__DIR__, 2);
$pluginDirs = \scandir($pluginDirectory);

if (!\is_array($pluginDirs)) {
    echo 'Could not check plugin directory';
    $pluginDirs = [];
}

foreach ($pluginDirs as $pluginDir) {
    foreach ($optionalPlugins as $plugin => $path) {
        if (\file_exists($pluginDirectory . '/' . $pluginDir . '/' . $path)) {
            unset($optionalPlugins[$plugin]);

            $pathToAutoload = $pluginDirectory . '/' . $pluginDir . '/vendor/autoload.php';

            if (\file_exists($pathToAutoload)) {
                require_once $pathToAutoload;
            } else {
                echo "Please execute 'composer dump-autoload --dev' in your $plugin directory\n";
            }
        }
    }
}

foreach ($optionalPlugins as $plugin => $path) {
    echo 'You need the ' . $plugin . " plugin for static analyze to work properly.\n";
}

$projectRoot = dirname(__DIR__, 4);
$classLoader = require $projectRoot . '/vendor/autoload.php';
if (file_exists($projectRoot . '/.env')) {
    (new Dotenv())->usePutEnv()->load($projectRoot . '/.env');
}
