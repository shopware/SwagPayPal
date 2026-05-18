<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Storefront\Snippet;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Snippet\Files\GenericSnippetFile;
use Shopware\Core\System\Snippet\Files\SnippetFileCollection;
use Shopware\Core\System\Snippet\Files\SnippetFileLoaderInterface;
use Swag\PayPal\Storefront\Framework\Snippet\SnippetFileLoaderDecorator;

/**
 * @deprecated tag:v11.0.0 - Will be removed without replacement if minimum version is >=6.7.3.0
 *
 * @internal
 */
#[Package('checkout')]
class SnippetFileLoaderDecoratorTest extends TestCase
{
    private SnippetFileLoaderInterface&MockObject $inner;

    private SnippetFileLoaderDecorator $decorator;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(SnippetFileLoaderInterface::class);
        $this->decorator = new SnippetFileLoaderDecorator($this->inner);
    }

    public function testLoadSnippetFilesIntoCollection(): void
    {
        $collection = new SnippetFileCollection([
            new GenericSnippetFile(
                'paypal.en',
                'some-path',
                'en',
                'shopware AG',
                false,
                'SwagPayPal',
            ),
            new GenericSnippetFile(
                'paypal.de',
                'some-path',
                'de',
                'shopware AG',
                false,
                'SwagPayPal',
            ),
        ]);

        $this->inner
            ->expects($this->once())
            ->method('loadSnippetFilesIntoCollection')
            ->with($collection);

        $this->decorator->loadSnippetFilesIntoCollection($collection);

        static::assertSame([[
            'name' => 'paypal.en',
            'iso' => 'en-GB',
            'path' => 'some-path',
            'author' => 'shopware AG',
            'isBase' => false,
        ], [
            'name' => 'paypal.de',
            'iso' => 'de-DE',
            'path' => 'some-path',
            'author' => 'shopware AG',
            'isBase' => false,
        ]], $collection->toArray());
    }

    #[DataProvider('dataProviderNonMatchingSnippetFiles')]
    public function testLoadSnippetFilesIntoCollectionNonMatchingSnippetFiles(GenericSnippetFile $file): void
    {
        $expected = new SnippetFileCollection([$file]);
        $collection = new SnippetFileCollection([$file]);

        $this->inner
            ->expects($this->once())
            ->method('loadSnippetFilesIntoCollection')
            ->with($collection);

        $this->decorator->loadSnippetFilesIntoCollection($collection);

        static::assertEquals($expected, $collection);
    }

    public function testAddsDefaultSnippetsIfOnlyUnrelatedLocaleTranslationExists(): void
    {
        $collection = new SnippetFileCollection([
            new GenericSnippetFile(
                'storefront',
                'translation/locale/fr-FR/Plugins/SwagPayPal/storefront.json',
                'fr-FR',
                'Shopware',
                false,
                'Plugins',
            ),
        ]);

        $this->inner
            ->expects($this->once())
            ->method('loadSnippetFilesIntoCollection')
            ->with($collection);

        $this->decorator->loadSnippetFilesIntoCollection($collection);

        $enFiles = $collection->getSnippetFilesByIso('en-GB');
        $deFiles = $collection->getSnippetFilesByIso('de-DE');

        static::assertCount(1, $enFiles);
        static::assertCount(1, $deFiles);
        static::assertSame('paypal.en', $enFiles[0]->getName());
        static::assertSame('paypal.de', $deFiles[0]->getName());
        static::assertSame('SwagPayPal', $enFiles[0]->getTechnicalName());
        static::assertSame('SwagPayPal', $deFiles[0]->getTechnicalName());
    }

    public function testDoesNotAddDefaultSnippetIfSameLocaleTranslationExists(): void
    {
        $collection = new SnippetFileCollection([
            new GenericSnippetFile(
                'storefront',
                'translation/locale/fr-FR/Plugins/SwagPayPal/storefront.json',
                'fr-FR',
                'Shopware',
                false,
                'Plugins',
            ),
            new GenericSnippetFile(
                'storefront',
                'translation/locale/de-DE/Plugins/SwagPayPal/storefront.json',
                'de-DE',
                'Shopware',
                false,
                'Plugins',
            ),
        ]);

        $this->inner
            ->expects($this->once())
            ->method('loadSnippetFilesIntoCollection')
            ->with($collection);

        $this->decorator->loadSnippetFilesIntoCollection($collection);

        $enFiles = $collection->getSnippetFilesByIso('en-GB');
        $deFiles = $collection->getSnippetFilesByIso('de-DE');

        static::assertCount(1, $enFiles);
        static::assertCount(1, $deFiles);
        static::assertSame('paypal.en', $enFiles[0]->getName());
        static::assertSame('SwagPayPal', $enFiles[0]->getTechnicalName());
        static::assertSame('storefront', $deFiles[0]->getName());
        static::assertSame('Plugins', $deFiles[0]->getTechnicalName());
    }

    public static function dataProviderNonMatchingSnippetFiles(): \Generator
    {
        yield 'wrong technical name' => [new GenericSnippetFile(
            'paypal.en',
            'some-path',
            'en',
            'shopware AG',
            false,
            'SwagPayPalg',
        )];

        yield 'wrong file naming' => [new GenericSnippetFile(
            'paypal2.en',
            'some-path',
            'en',
            'shopware AG',
            false,
            'SwagPayPal',
        )];

        yield 'wrong iso' => [new GenericSnippetFile(
            'paypal.en',
            'some-path',
            'fr',
            'shopware AG',
            false,
            'SwagPayPal',
        )];
    }
}
