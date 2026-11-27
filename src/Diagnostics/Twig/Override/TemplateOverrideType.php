<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Diagnostics\Twig\Override;

use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[Package('checkout')]
enum TemplateOverrideType: string
{
    case MissingParent = 'missing_parent';

    /**
     * `parent()` is only called inside an `if` or `for`
     */
    case ConditionalParent = 'conditional_parent';

    /**
     * Re-declared only inside an `if` or `for`
     */
    case ConditionalBlock = 'conditional_block';
    case TemplateReplaced = 'template_replaced';

    /**
     * Twig's `extends` instead of `sw_extends` jumps past the PayPal template
     */
    case PluginTemplateSkipped = 'plugin_template_skipped';
    case DynamicExtends = 'dynamic_extends';

    /**
     * Parsing outside a storefront request may fail where rendering the storefront doesn't
     */
    case Unreadable = 'unreadable';

    public function severity(): Severity
    {
        return match ($this) {
            self::MissingParent, self::TemplateReplaced, self::PluginTemplateSkipped => Severity::Error,
            self::ConditionalParent, self::ConditionalBlock, self::DynamicExtends => Severity::Warning,
            self::Unreadable => Severity::Unknown,
        };
    }
}
