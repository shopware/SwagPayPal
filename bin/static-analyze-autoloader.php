<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once '../../../vendor/autoload.php';

// find CMS Extensions
foreach (scandir('../') as $directoryItem) {
    if (is_dir('../' . $directoryItem) && file_exists('../' . $directoryItem . '/src/SwagCmsExtensions.php')) {
        $pathToCmsExtensions = '../' . $directoryItem . '/vendor/autoload.php';
        if (file_exists($pathToCmsExtensions)) {
            require_once $pathToCmsExtensions;
        } else {
            echo "Please execute 'composer dump-autoload' in your CmsExtensions directory\n";
        }
        exit();
    }
}

echo "You need the CmsExtensions plugin for static analyze to work.\n";
