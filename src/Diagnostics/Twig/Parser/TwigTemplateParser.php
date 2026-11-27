<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Parser;

use Shopware\Core\Framework\Log\Package;
use Twig\Environment;
use Twig\Node\BlockNode;
use Twig\Node\BlockReferenceNode;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\ParentExpression;
use Twig\Node\ForNode;
use Twig\Node\IfNode;
use Twig\Node\Node;
use Twig\Source;
use Twig\Token;
use Twig\TokenStream;

/**
 * `sw_extends` skips templates whose scopes don't match the request, so they come back without blocks.
 *
 * @internal
 */
#[Package('checkout')]
class TwigTemplateParser
{
    // Twig drops comments while lexing, so the markers are read from the source
    private const MARKER_PATTERN = '/\{#-?\s*@diagnostics\s+([a-z0-9_]+)\s*-?#\}\s*\{%-?\s*block\s+([A-Za-z0-9_]+)/';

    public function __construct(private readonly Environment $twig)
    {
    }

    public function parse(Source $source): TemplateStructure
    {
        $stream = $this->twig->tokenize($source);

        // Read before parsing, Shopware replaces `sw_extends` by `extends` while parsing
        [$extendsTag, $extendsLine] = $this->findExtendsTag($stream);

        $module = $this->twig->parse($stream);

        $parent = $module->hasNode('parent') ? $module->getNode('parent') : null;
        $extendsTarget = $parent instanceof ConstantExpression && \is_string($parent->getAttribute('value')) ? $parent->getAttribute('value') : null;

        $blockNodes = [];
        foreach ($module->getNode('blocks') as $name => $node) {
            $block = $node instanceof BlockNode ? $node : $this->findBlockNode($node);
            if ($block !== null) {
                $blockNodes[(string) $name] = $block;
            }
        }

        $nesting = [];
        foreach ($blockNodes as $outer => $block) {
            foreach ($this->findNestedBlocks($block->getNode('body')) as $inner => $conditional) {
                $nesting[$inner] ??= [$outer, $conditional];
            }
        }

        $definitions = [];
        foreach ($blockNodes as $name => $block) {
            [$parentBlock, $conditional] = $nesting[$name] ?? [null, false];
            $definitions[$name] = new BlockDefinition($name, $block->getTemplateLine(), $parentBlock, $conditional, $this->findParentCall($block->getNode('body')));
        }

        return new TemplateStructure(
            name: $source->getName(),
            file: $source->getPath() === '' ? null : $source->getPath(),
            extendsTag: $parent === null ? null : $extendsTag,
            extendsTarget: $extendsTarget,
            extendsLine: $extendsLine,
            blocks: $definitions,
            checkedBlocks: $this->readCheckedBlocks($source->getCode()),
        );
    }

    /**
     * @return array<string, string>
     */
    private function readCheckedBlocks(string $code): array
    {
        \preg_match_all(self::MARKER_PATTERN, $code, $matches, \PREG_SET_ORDER);

        $checkedBlocks = [];
        foreach ($matches as [, $feature, $block]) {
            $checkedBlocks[$block] = $feature;
        }

        return $checkedBlocks;
    }

    /**
     * @return array{0: TemplateStructure::TAG_*, 1: int}
     */
    private function findExtendsTag(TokenStream $stream): array
    {
        for ($offset = 0; !($token = $stream->look($offset))->test(Token::EOF_TYPE); ++$offset) {
            if (!$token->test(Token::BLOCK_START_TYPE)) {
                continue;
            }

            $tag = $stream->look($offset + 1);
            if ($tag->test(Token::NAME_TYPE, TemplateStructure::TAG_SW_EXTENDS)) {
                return [TemplateStructure::TAG_SW_EXTENDS, $tag->getLine()];
            }

            if ($tag->test(Token::NAME_TYPE, TemplateStructure::TAG_EXTENDS)) {
                return [TemplateStructure::TAG_EXTENDS, $tag->getLine()];
            }
        }

        return [TemplateStructure::TAG_EXTENDS, 0];
    }

    private function findBlockNode(Node $node): ?BlockNode
    {
        foreach ($node as $child) {
            if ($child instanceof BlockNode) {
                return $child;
            }
        }

        return null;
    }

    private function findParentCall(Node $node, bool $conditional = false): ParentCall
    {
        if ($node instanceof ParentExpression) {
            return $conditional ? ParentCall::Conditional : ParentCall::Always;
        }

        $conditional = $conditional || $node instanceof IfNode || $node instanceof ForNode;

        $found = ParentCall::None;
        foreach ($node as $child) {
            $call = $this->findParentCall($child, $conditional);
            if ($call === ParentCall::Always) {
                return $call;
            }

            $found = $call === ParentCall::Conditional ? $call : $found;
        }

        return $found;
    }

    /**
     * Nested blocks aren't part of a block's body, Twig puts a reference to them there.
     *
     * @return array<string, bool> block names mapped to whether they sit inside an `if` or `for`
     */
    private function findNestedBlocks(Node $node, bool $conditional = false): array
    {
        if ($node instanceof BlockReferenceNode) {
            return [(string) $node->getAttribute('name') => $conditional];
        }

        $conditional = $conditional || $node instanceof IfNode || $node instanceof ForNode;

        $nested = [];
        foreach ($node as $child) {
            $nested += $this->findNestedBlocks($child, $conditional);
        }

        return $nested;
    }
}
