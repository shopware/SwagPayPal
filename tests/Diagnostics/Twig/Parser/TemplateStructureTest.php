<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Parser;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Twig\Parser\TemplateStructure;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TemplateStructure::class)]
class TemplateStructureTest extends TestCase
{
    #[DataProvider('templateNameProvider')]
    public function testSplitsTemplateNames(string $templateName, ?string $namespace, string $path): void
    {
        static::assertSame($namespace, TemplateStructure::namespaceOf($templateName));
        static::assertSame($path, TemplateStructure::pathOf($templateName));
    }

    public static function templateNameProvider(): \Generator
    {
        yield 'namespaced name' => ['@SwagPayPal/storefront/base.html.twig', 'SwagPayPal', 'storefront/base.html.twig'];
        yield 'name without namespace' => ['storefront/base.html.twig', null, 'storefront/base.html.twig'];
        yield 'namespace without path' => ['@SwagPayPal', null, '@SwagPayPal'];
    }

    public function testNamespaceOfTheTemplate(): void
    {
        static::assertSame('SwagPayPal', $this->structure('@SwagPayPal/storefront/base.html.twig')->getNamespace());
        static::assertSame('', $this->structure('storefront/base.html.twig')->getNamespace());
    }

    private function structure(string $name): TemplateStructure
    {
        return new TemplateStructure(
            name: $name,
            file: '',
            extendsTag: null,
            extendsTarget: null,
            extendsLine: 0,
            blocks: [],
            checkedBlocks: [],
        );
    }
}
