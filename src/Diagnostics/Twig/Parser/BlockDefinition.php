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
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
final class BlockDefinition extends Struct
{
    public function __construct(
        public readonly string $name,
        public readonly int $line,
        public readonly ?string $parentBlock,
        /**
         * Inside an `if` or `for` of its parent block
         */
        public readonly bool $conditional,
        public readonly ParentCall $parentCall,
    ) {
    }
}
