<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Plugin\Composer\Factory;
use Symfony\Component\Finder\Finder;

class DeprecatedTagTest extends TestCase
{
    /**
     * white list file path segments for ignored paths
     *
     * @var array
     */
    private $whiteList = [
        'tests/',
        'Resources/public/',
    ];

    public function testAllPhpFilesInPlatformForDeprecated(): void
    {
        $return = [];
        $finder = new Finder();
        $finder->in('./../')
            ->files()
            ->contains('@deprecated');

        foreach ($this->whiteList as $path) {
            $finder->notPath($path);
        }

        foreach ($finder->getIterator() as $phpFile) {
            if ($this->hasDeprecationFalseOrNoTag('@deprecated', $phpFile->getPathname())) {
                $return[] = $phpFile->getPathname();
            }
        }

        $finder = new Finder();
        $finder->in('./../')
            ->files()
            ->name('*.xml')
            ->contains('<deprecated>');

        foreach ($this->whiteList as $path) {
            $finder->notPath($path);
        }

        foreach ($finder->getIterator() as $xmlFile) {
            if ($this->hasDeprecationFalseOrNoTag('\<deprecated\>', $xmlFile->getPathname())) {
                $return[] = $xmlFile->getPathname();
            }
        }

        static::assertSame([], $return, print_r($return, true));
    }

    private function hasDeprecationFalseOrNoTag(string $deprecatedPrefix, string $file): bool
    {
        $content = file_get_contents($file);
        static::assertNotFalse($content, sprintf('File "%s" not found or not readable', $file));
        $matches = [];
        $pattern = '/' . $deprecatedPrefix . '(?!\s?tag\:)/';
        preg_match($pattern, $content, $matches);

        if (!empty(array_filter($matches))) {
            return true;
        }

        $pattern = '/' . $deprecatedPrefix . '\s?tag\:v{1}([0-9,\.]{2,5})/';
        preg_match_all($pattern, $content, $matches);

        $matches = $matches[1];

        if (empty(array_filter($matches))) {
            return true;
        }

        $currentPluginVersion = $this->getCurrentPluginVersion();

        foreach ($matches as $match) {
            if (version_compare($currentPluginVersion, $match) !== -1) {
                return true;
            }
        }

        return false;
    }

    private function getCurrentPluginVersion(): string
    {
        return Factory::createComposer(__DIR__ . '/..')->getPackage()->getPrettyVersion();
    }
}
