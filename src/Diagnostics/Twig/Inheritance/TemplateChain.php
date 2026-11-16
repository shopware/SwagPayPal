<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Inheritance;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\Struct;
use Twig\Source;

/**
 * @internal
 *
 * @codeCoverageIgnore
 */
#[Package('checkout')]
final class TemplateChain extends Struct
{
    /**
     * @param list<Source> $above most derived first
     * @param list<Source> $below most derived first
     */
    public function __construct(
        public readonly string $templatePath,
        public readonly array $above,
        public readonly Source $plugin,
        public readonly array $below,
    ) {
    }
}
