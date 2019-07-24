<?php declare(strict_types=1);

namespace Swag\PayPal\Resources\snippet\de_DE;

use Shopware\Core\Framework\Snippet\Files\SnippetFileInterface;

class SnippetFile_de_DE implements SnippetFileInterface
{
    public function getName(): string
    {
        return 'paypal.de-DE';
    }

    public function getPath(): string
    {
        return __DIR__ . '/paypal.de-DE.json';
    }

    public function getIso(): string
    {
        return 'de-DE';
    }

    public function getAuthor(): string
    {
        return 'Shopware Services';
    }

    public function isBase(): bool
    {
        return false;
    }
}
