<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\Rector;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\ClassLike;
use Rector\Core\Rector\AbstractRector;
use Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Shopware\Core\Framework\Log\Package;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

#[Package('checkout')]
class ClassCheckoutPackageRector extends AbstractRector
{
    private const AREA_CHECKOUT = 'checkout';

    public function __construct(private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory)
    {
    }

    public function getNodeTypes(): array
    {
        return [ClassLike::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (!$node instanceof ClassLike) {
            return null;
        }

        if ($this->hasPackageAnnotation($node)) {
            return null;
        }

        $node->attrGroups[] = $this->phpAttributeGroupFactory->createFromClassWithItems(Package::class, [self::AREA_CHECKOUT]);

        return $node;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Adds a #[Package(\'checkout\')] attribute to all php classes.',
            [
                new CodeSample(
                    // code before
                    '
class Foo{}',

                    // code after
                    '
#[Package(\'checkout\')]
class Foo{}'
                ),
            ]
        );
    }

    private function hasPackageAnnotation(ClassLike $class): bool
    {
        $names = \array_map(
            fn (AttributeGroup $group) => $group->attrs[0]->name->toString(),
            $class->attrGroups
        );

        return \in_array(Package::class, $names, true) || \in_array('Package', $names, true);
    }
}
