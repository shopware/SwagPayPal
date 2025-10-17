<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Storefront\Framework\Snippet;

use Shopware\Core\Framework\Log\Package;
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
    }
}
