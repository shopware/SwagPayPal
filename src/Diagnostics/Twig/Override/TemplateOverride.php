<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Override;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Swag\PayPal\Diagnostics\Severity;

/**
 * A template of another extension or theme that keeps checked PayPal blocks from rendering.
 *
 * @internal
 */
#[OA\Schema(
    schema: 'swag_paypal_diagnostics_twig_template_override',
    required: ['type', 'severity', 'templatePath', 'namespace', 'file', 'line', 'block', 'affected'],
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['missing_parent', 'conditional_parent', 'conditional_block', 'template_replaced', 'plugin_template_skipped', 'dynamic_extends', 'unreadable']),
        new OA\Property(property: 'severity', description: '`error` when PayPal blocks don\'t render, `warning` when they only render under some conditions, `unknown` when the check couldn\'t tell', type: 'string', enum: ['error', 'warning', 'notice', 'unknown']),
        new OA\Property(property: 'templatePath', description: 'For example `storefront/page/checkout/confirm/index.html.twig` or `documents/invoice.html.twig`', type: 'string'),
        new OA\Property(property: 'namespace', description: 'The extension, app or theme the overriding template belongs to', type: 'string'),
        new OA\Property(property: 'file', description: 'Relative to the project directory. Null for templates stored in the database, like the ones of apps', type: 'string', nullable: true),
        new OA\Property(property: 'line', type: 'integer'),
        new OA\Property(property: 'block', type: 'string', nullable: true),
        new OA\Property(
            property: 'affected',
            description: 'The checked PayPal blocks that don\'t render',
            type: 'array',
            items: new OA\Items(
                required: ['block', 'feature'],
                properties: [
                    new OA\Property(property: 'block', type: 'string'),
                    new OA\Property(property: 'feature', description: 'The feature named by the block\'s `@diagnostics` marker, for example `express_checkout_button`', type: 'string'),
                ],
                type: 'object',
            ),
        ),
    ],
)]
#[Package('checkout')]
final class TemplateOverride extends Struct
{
    public readonly Severity $severity;

    /**
     * @param array<string, string> $affectedBlocks
     */
    public function __construct(
        public readonly TemplateOverrideType $type,
        public readonly string $templatePath,
        public readonly string $namespace,
        public readonly ?string $file,
        public readonly int $line,
        public readonly ?string $block,
        public readonly array $affectedBlocks,
    ) {
        $this->severity = $type->severity();
    }

    /**
     * @param array<string, string> $affectedBlocks
     */
    public function withAffectedBlocks(array $affectedBlocks): self
    {
        return new self($this->type, $this->templatePath, $this->namespace, $this->file, $this->line, $this->block, $affectedBlocks);
    }

    public function withAffectedBlock(string $block, string $feature): self
    {
        return $this->withAffectedBlocks([...$this->affectedBlocks, $block => $feature]);
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        unset($data['affectedBlocks']);

        return [
            ...$data,
            'affected' => \array_map(
                static fn (string $block, string $feature) => ['block' => $block, 'feature' => $feature],
                \array_keys($this->affectedBlocks),
                $this->affectedBlocks,
            ),
        ];
    }
}
