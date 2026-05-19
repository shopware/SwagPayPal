<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Framework\Snippet;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\Snippet\Files\AbstractSnippetFile;
use Shopware\Core\System\Snippet\Files\GenericSnippetFile;
use Shopware\Core\System\Snippet\Files\SnippetFileCollection;
use Shopware\Core\System\Snippet\Files\SnippetFileLoaderInterface;

/**
 * @deprecated tag:v11.0.0 - Will be removed without replacement if minimum version is >=6.7.3.0
 *
 * @internal
 */
#[Package('checkout')]
class SnippetFileLoaderDecorator implements SnippetFileLoaderInterface
{
    private const PLUGIN_NAME = 'SwagPayPal';
    private const TRANSLATION_TECHNICAL_NAME = 'Plugins';

    private const ISO_MAP = [
        'en' => 'en-GB',
        'de' => 'de-DE',
    ];

    public function __construct(
        private readonly SnippetFileLoaderInterface $inner,
    ) {
    }

    public function loadSnippetFilesIntoCollection(SnippetFileCollection $snippetFileCollection): void
    {
        $this->inner->loadSnippetFilesIntoCollection($snippetFileCollection);

        foreach ($snippetFileCollection as $key => $snippetFile) {
            if ($snippetFile->getTechnicalName() !== 'SwagPayPal' || !\str_starts_with($snippetFile->getName(), 'paypal.') || !\array_key_exists($snippetFile->getIso(), self::ISO_MAP)) {
                continue;
            }

            $snippetFileCollection->set($key, new GenericSnippetFile(
                $snippetFile->getName(),
                $snippetFile->getPath(),
                self::ISO_MAP[$snippetFile->getIso()],
                $snippetFile->getAuthor(),
                $snippetFile->isBase(),
                $snippetFile->getTechnicalName(),
            ));
        }

        if (!$this->hasPayPalTranslationSnippet($snippetFileCollection)) {
            return;
        }

        foreach ($this->getBundledPayPalSnippetFiles() as $iso => $snippet) {
            if ($this->hasPayPalSnippetForIso($snippetFileCollection, $iso)) {
                continue;
            }

            $snippetFileCollection->add(new GenericSnippetFile(
                $snippet['name'],
                $snippet['path'],
                $iso,
                'shopware AG',
                false,
                self::PLUGIN_NAME,
            ));
        }
    }

    /**
     * @return array<string, array{name: string, path: string}>
     */
    private function getBundledPayPalSnippetFiles(): array
    {
        $snippetFiles = [];
        $paths = \glob($this->getSnippetDirectory() . '/paypal.*.json') ?: [];

        \sort($paths);

        foreach ($paths as $path) {
            $name = \basename($path, '.json');
            $nameParts = \explode('.', $name);

            if (\count($nameParts) !== 2) {
                continue;
            }

            $iso = self::ISO_MAP[$nameParts[1]] ?? $nameParts[1];
            $snippetFiles[$iso] = [
                'name' => $name,
                'path' => $path,
            ];
        }

        return $snippetFiles;
    }

    private function getSnippetDirectory(): string
    {
        return \dirname(__DIR__, 3) . '/Resources/snippet';
    }

    private function hasPayPalTranslationSnippet(SnippetFileCollection $snippetFileCollection): bool
    {
        foreach ($snippetFileCollection as $snippetFile) {
            if ($this->isPayPalTranslationSnippet($snippetFile)) {
                return true;
            }
        }

        return false;
    }

    private function hasPayPalSnippetForIso(SnippetFileCollection $snippetFileCollection, string $iso): bool
    {
        foreach ($snippetFileCollection as $snippetFile) {
            if ($snippetFile->getIso() !== $iso) {
                continue;
            }

            if ($this->isPayPalBundledSnippet($snippetFile) || $this->isPayPalTranslationSnippet($snippetFile)) {
                return true;
            }
        }

        return false;
    }

    private function isPayPalBundledSnippet(AbstractSnippetFile $snippetFile): bool
    {
        return $snippetFile->getTechnicalName() === self::PLUGIN_NAME
            && \str_starts_with($snippetFile->getName(), 'paypal.');
    }

    private function isPayPalTranslationSnippet(AbstractSnippetFile $snippetFile): bool
    {
        $path = \str_replace('\\', '/', $snippetFile->getPath());

        // Shopware translation files are stored by plugin, e.g. translations/de-DE/Plugins/SwagPayPal/Storefront.
        return $snippetFile->getTechnicalName() === self::TRANSLATION_TECHNICAL_NAME
            && \str_contains($path, '/Plugins/' . self::PLUGIN_NAME . '/');
    }
}
