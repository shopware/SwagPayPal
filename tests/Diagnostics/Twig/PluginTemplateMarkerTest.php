<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChainLoader;
use Swag\PayPal\Diagnostics\Twig\Parser\TwigTemplateParser;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

/**
 * Markers that aren't directly above a block are silently ignored, so this test keeps them in place.
 *
 * @internal
 */
#[Package('checkout')]
class PluginTemplateMarkerTest extends TestCase
{
    use KernelTestBehaviour;

    public function testEveryMarkerSitsDirectlyAboveABlockOfTheTemplate(): void
    {
        $twig = static::getContainer()->get('twig');
        static::assertInstanceOf(Environment::class, $twig);
        $parser = new TwigTemplateParser($twig);

        $markers = 0;
        $misplaced = [];
        $views = __DIR__ . '/../../../src/Resources/views/';
        $files = (new Finder())->files()->in(\array_map(static fn (TemplateArea $area) => $views . $area->value, TemplateArea::cases()))->name('*.twig');
        foreach ($files as $file) {
            $templatePath = \str_replace($views, '', $file->getPathname());
            $source = $twig->getLoader()->getSourceContext('@' . TemplateChainLoader::PLUGIN_NAMESPACE . '/' . $templatePath);
            if (!\str_contains($source->getCode(), '@diagnostics')) {
                continue;
            }

            $template = $parser->parse($source);

            $markers += \count($template->checkedBlocks);
            if (\substr_count($source->getCode(), '@diagnostics') !== \count($template->checkedBlocks)) {
                $misplaced[] = $templatePath;
            }

            foreach (\array_keys($template->checkedBlocks) as $block) {
                if (!isset($template->blocks[$block])) {
                    $misplaced[] = $templatePath . ': ' . $block;
                }
            }
        }

        static::assertSame([], $misplaced, 'Markers must sit directly above a block, like {# @diagnostics express_checkout_button #}');
        static::assertGreaterThan(0, $markers);
    }
}
