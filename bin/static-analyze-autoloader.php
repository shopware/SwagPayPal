<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';

/**
 * Please note that we cannot use exit() in this file!
 * Because this would exit the startup of our analyze tools such as Psalm and Phpstan.
 */
$cmsExtensionsFound = false;
$files = \scandir('../');
if (\is_array($files)) {
    foreach ($files as $file) {
        if (\is_dir('../' . $file) && \file_exists('../' . $file . '/src/SwagCmsExtensions.php')) {
            $cmsExtensionsFound = true;
            $pathToCmsExtensions = '../' . $file . '/vendor/autoload.php';
            if (\file_exists($pathToCmsExtensions)) {
                require_once $pathToCmsExtensions;
            } else {
                echo "Please execute 'composer dump-autoload' in your CmsExtensions directory\n";
            }
        }
    }

    if (!$cmsExtensionsFound) {
        echo "You need the CmsExtensions plugin for static analyze to work.\n";
    }
} else {
    echo 'Could not scandir ../';
}
