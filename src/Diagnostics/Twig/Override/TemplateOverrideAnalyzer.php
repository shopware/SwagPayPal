<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Override;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Twig\Inheritance\TemplateChain;
use Swag\PayPal\Diagnostics\Twig\Parser\BlockDefinition;
use Swag\PayPal\Diagnostics\Twig\Parser\ParentCall;
use Swag\PayPal\Diagnostics\Twig\Parser\TemplateStructure;
use Swag\PayPal\Diagnostics\Twig\Parser\TwigTemplateParser;
use Twig\Error\Error;
use Twig\Source;

/**
 * @internal
 */
#[Package('checkout')]
class TemplateOverrideAnalyzer
{
    public function __construct(private readonly TwigTemplateParser $parser)
    {
    }

    /**
     * @return list<TemplateOverride>
     */
    public function analyze(TemplateChain $chain): array
    {
        try {
            $plugin = $this->parser->parse($chain->plugin);
            if ($plugin->checkedBlocks === []) {
                return [];
            }

            $above = \array_map($this->parser->parse(...), $chain->above);
            $below = \array_map($this->parser->parse(...), $chain->below);
        } catch (Error $error) {
            return [$this->unreadable($chain, $error->getSourceContext() ?? $chain->plugin, $error->getTemplateLine())];
        }

        $rendered = $this->findRendered($above);

        $chainBreak = $this->findChainBreak($chain->templatePath, $rendered, [...$above, $plugin]);
        if ($chainBreak !== null) {
            return [$chainBreak->withAffectedBlocks($plugin->checkedBlocks)];
        }

        return $this->collectHiddenBlocks($chain->templatePath, $plugin, $below, $rendered);
    }

    /**
     * @param list<TemplateStructure> $above most derived first
     *
     * @return list<TemplateStructure> the ones Twig renders, most derived first
     */
    private function findRendered(array $above): array
    {
        $rendered = [];
        $jumpTo = null;

        foreach ($above as $template) {
            if ($jumpTo !== null && $template->getNamespace() !== $jumpTo) {
                continue;
            }

            $rendered[] = $template;

            // `extends` loads the named template, `sw_extends` the next one in the chain
            $jumpTo = $template->extendsTag === TemplateStructure::TAG_EXTENDS ? TemplateStructure::namespaceOf((string) $template->extendsTarget) : null;
        }

        return $rendered;
    }

    /**
     * A template that leaves the chain hides every checked block.
     *
     * @param list<TemplateStructure> $rendered
     * @param list<TemplateStructure> $reachable
     */
    private function findChainBreak(string $templatePath, array $rendered, array $reachable): ?TemplateOverride
    {
        $reachableNamespaces = \array_map(static fn (TemplateStructure $template) => $template->getNamespace(), $reachable);

        foreach ($rendered as $template) {
            $type = $this->getChainBreakType($template, $templatePath, $reachableNamespaces);

            if ($type !== null) {
                return $this->override($type, $templatePath, $template, $template->extendsLine, null);
            }
        }

        return null;
    }

    /**
     * @param list<string> $reachableNamespaces
     */
    private function getChainBreakType(TemplateStructure $template, string $templatePath, array $reachableNamespaces): ?TemplateOverrideType
    {
        if ($template->extendsTag === null) {
            return TemplateOverrideType::TemplateReplaced;
        }

        if ($template->extendsTarget === null) {
            return TemplateOverrideType::DynamicExtends;
        }

        if (TemplateStructure::pathOf($template->extendsTarget) !== $templatePath) {
            return TemplateOverrideType::TemplateReplaced;
        }

        $jumpsPastThePlugin = !\in_array(TemplateStructure::namespaceOf($template->extendsTarget), $reachableNamespaces, true);
        if ($template->extendsTag === TemplateStructure::TAG_EXTENDS && $jumpsPastThePlugin) {
            return TemplateOverrideType::PluginTemplateSkipped;
        }

        return null;
    }

    /**
     * @param list<TemplateStructure> $below most derived first
     * @param list<TemplateStructure> $rendered most derived first
     *
     * @return list<TemplateOverride>
     */
    private function collectHiddenBlocks(string $templatePath, TemplateStructure $plugin, array $below, array $rendered): array
    {
        $parentBlocks = $this->collectParentBlocks([$plugin, ...$below]);

        $overrides = [];
        foreach ($plugin->checkedBlocks as $block => $feature) {
            $hiding = $this->findHidingOverride($templatePath, $block, $rendered, $parentBlocks);

            if ($hiding !== null) {
                $key = $hiding->type->value . '|' . $hiding->namespace . '|' . $hiding->block;
                $overrides[$key] = ($overrides[$key] ?? $hiding)->withAffectedBlock($block, $feature);
            }
        }

        return \array_values($overrides);
    }

    /**
     * @param list<TemplateStructure> $templates most derived first
     *
     * @return array<string, string> block name mapped to its parent block
     */
    private function collectParentBlocks(array $templates): array
    {
        $parentBlocks = [];

        // Least derived first, so the plugin's own nesting wins
        foreach (\array_reverse($templates) as $template) {
            foreach ($template->blocks as $definition) {
                if ($definition->parentBlock !== null) {
                    $parentBlocks[$definition->name] = $definition->parentBlock;
                }
            }
        }

        return $parentBlocks;
    }

    /**
     * An override further out decides whether inner blocks render, so the walk starts at the outermost one.
     *
     * @param list<TemplateStructure> $rendered most derived first
     * @param array<string, string> $parentBlocks
     */
    private function findHidingOverride(string $templatePath, string $block, array $rendered, array $parentBlocks): ?TemplateOverride
    {
        $path = [...\array_reverse($this->collectAncestors($block, $parentBlocks)), $block];

        return $this->walkPath($templatePath, $path, $rendered);
    }

    /**
     * @param list<string> $path outermost block first, the first one is the block to inspect
     * @param list<TemplateStructure> $rendered most derived first
     */
    private function walkPath(string $templatePath, array $path, array $rendered): ?TemplateOverride
    {
        if ($path === []) {
            return null;
        }

        foreach ($rendered as $template) {
            $definition = $template->blocks[$path[0]] ?? null;
            if ($definition === null) {
                continue;
            }

            $redeclared = $this->findRedeclaredBlock($template, $path);
            if ($redeclared !== null) {
                // Rendering continues with the re-declared block, the ones in between are bypassed
                return $this->isConditional($template, $path[$redeclared], $path[0])
                    ? $this->override(TemplateOverrideType::ConditionalBlock, $templatePath, $template, $definition->line, $path[0])
                    : $this->walkPath($templatePath, \array_slice($path, $redeclared), $rendered);
            }

            if ($definition->parentCall !== ParentCall::Always) {
                $type = $definition->parentCall === ParentCall::Conditional ? TemplateOverrideType::ConditionalParent : TemplateOverrideType::MissingParent;

                return $this->override($type, $templatePath, $template, $definition->line, $path[0]);
            }
        }

        return $this->walkPath($templatePath, \array_slice($path, 1), $rendered);
    }

    /**
     * @param array<string, string> $parentBlocks
     *
     * @return list<string>
     */
    private function collectAncestors(string $block, array $parentBlocks): array
    {
        $ancestors = [];
        $current = $parentBlocks[$block] ?? null;

        while ($current !== null && !\in_array($current, $ancestors, true) && $current !== $block) {
            $ancestors[] = $current;
            $current = $parentBlocks[$current] ?? null;
        }

        return $ancestors;
    }

    /**
     * @param list<string> $path
     *
     * @return int|null the position in the path of the first inner block that the template re-declares inside the first one
     */
    private function findRedeclaredBlock(TemplateStructure $template, array $path): ?int
    {
        for ($position = 1; $position < \count($path); ++$position) {
            if ($this->getBlocksBetween($template, $path[$position], $path[0]) !== []) {
                return $position;
            }
        }

        return null;
    }

    private function isConditional(TemplateStructure $template, string $inner, string $outer): bool
    {
        foreach ($this->getBlocksBetween($template, $inner, $outer) as $block) {
            if ($block->conditional) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<BlockDefinition> from the inner block up to the one directly inside the outer one, empty if not nested there
     */
    private function getBlocksBetween(TemplateStructure $template, string $inner, string $outer): array
    {
        $between = [];
        $block = $template->blocks[$inner] ?? null;

        while ($block !== null) {
            $between[] = $block;

            if ($block->parentBlock === $outer) {
                return $between;
            }

            $block = $block->parentBlock === null ? null : $template->blocks[$block->parentBlock];
        }

        return [];
    }

    private function unreadable(TemplateChain $chain, Source $source, int $line): TemplateOverride
    {
        return new TemplateOverride(
            type: TemplateOverrideType::Unreadable,
            templatePath: $chain->templatePath,
            namespace: TemplateStructure::namespaceOf($source->getName()) ?? '',
            file: $source->getPath() === '' ? null : $source->getPath(),
            line: $line,
            block: null,
            affectedBlocks: [],
        );
    }

    private function override(TemplateOverrideType $type, string $templatePath, TemplateStructure $template, int $line, ?string $block): TemplateOverride
    {
        return new TemplateOverride($type, $templatePath, $template->getNamespace(), $template->file, $line, $block, []);
    }
}
