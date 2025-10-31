<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Shopware\Core\TestBootstrapper;

function doesPluginExist(string $name): bool
{
    foreach (\scandir('../..') as $pluginDir) {
        $pathToComposerJson = $pluginDir . '/composer.json';

        if (!\file_exists($pathToComposerJson)) {
            continue;
        }

        $composer = json_decode((string) file_get_contents($pathToComposerJson), true, 512, \JSON_THROW_ON_ERROR);
        $pluginName = end(explode('\\', $composer['extra']['shopware-plugin-class'] ?? ''));

        if ($pluginName === $name) {
            return true;
        }
    }

    return false;
}

$plugins = ['SwagPayPal'];

if (doesPluginExist('SwagCmsExtensions')) {
    $plugins[] = 'SwagCmsExtensions';

    echo 'SwagCmsExtensions detected, require being active.' . \PHP_EOL;
}

return (new TestBootstrapper())
    ->setProjectDir($_SERVER['PROJECT_ROOT'] ?? dirname(__DIR__, 4))
    ->setLoadEnvFile(true)
    ->addActivePlugins(...$plugins)
    ->bootstrap()
    ->getClassLoader();
