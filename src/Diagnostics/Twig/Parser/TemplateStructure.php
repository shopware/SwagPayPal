<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Parser;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;

/**
 * @internal
 */
#[Package('checkout')]
final class TemplateStructure extends Struct
{
    public const TAG_SW_EXTENDS = 'sw_extends';
    public const TAG_EXTENDS = 'extends';

    /**
     * @param self::TAG_*|null $extendsTag
     * @param array<string, BlockDefinition> $blocks
     * @param array<string, string> $checkedBlocks block names mapped to the feature they render
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $file,
        public readonly ?string $extendsTag,
        public readonly ?string $extendsTarget,
        public readonly int $extendsLine,
        public readonly array $blocks,
        public readonly array $checkedBlocks,
    ) {
    }

    public static function namespaceOf(string $templateName): ?string
    {
        $slash = \mb_strpos($templateName, '/');
        if (!\str_starts_with($templateName, '@') || $slash === false) {
            return null;
        }

        return \mb_substr($templateName, 1, $slash - 1);
    }

    public static function pathOf(string $templateName): string
    {
        $slash = \mb_strpos($templateName, '/');
        if (!\str_starts_with($templateName, '@') || $slash === false) {
            return $templateName;
        }

        return \mb_substr($templateName, $slash + 1);
    }

    public function getNamespace(): string
    {
        return self::namespaceOf($this->name) ?? '';
    }
}
