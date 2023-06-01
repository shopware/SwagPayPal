<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use staabm\PHPStanDba\QueryReflection\PdoQueryReflector;
use staabm\PHPStanDba\QueryReflection\QueryReflection;
use staabm\PHPStanDba\QueryReflection\RuntimeConfiguration;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__, 4) . '/vendor/autoload.php';

$cmsExtensionsFound = false;
$pluginDirectory = dirname(__DIR__, 2);
$files = \scandir($pluginDirectory);

if (!\is_array($files)) {
    echo 'Could not check plugin directory';
}

foreach ($files as $file) {
    if (\file_exists($pluginDirectory . '/' . $file . '/src/SwagCmsExtensions.php')) {
        $cmsExtensionsFound = true;
        $pathToCmsExtensions = $pluginDirectory . '/' . $file . '/vendor/autoload.php';
        if (\file_exists($pathToCmsExtensions)) {
            require_once $pathToCmsExtensions;
        } else {
            echo "Please execute 'composer dump-autoload --dev' in your CmsExtensions directory\n";
        }
    }
}

if (!$cmsExtensionsFound) {
    echo "You need the CmsExtensions plugin for static analyze to work.\n";
}

$projectRoot = dirname(__DIR__, 4);
$classLoader = require $projectRoot . '/vendor/autoload.php';
if (file_exists($projectRoot . '/.env')) {
    (new Dotenv())->usePutEnv()->load($projectRoot . '/.env');
}
