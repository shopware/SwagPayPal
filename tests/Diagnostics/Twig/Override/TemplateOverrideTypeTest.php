<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics\Twig\Override;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideType;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(TemplateOverrideType::class)]
class TemplateOverrideTypeTest extends TestCase
{
    #[DataProvider('severityProvider')]
    public function testSeverity(TemplateOverrideType $type, Severity $severity): void
    {
        static::assertSame($severity, $type->severity());
    }

    public static function severityProvider(): \Generator
    {
        yield 'block overridden without parent() never renders' => [TemplateOverrideType::MissingParent, Severity::Error];
        yield 'replaced template never renders the PayPal template' => [TemplateOverrideType::TemplateReplaced, Severity::Error];
        yield 'Twig extends past the PayPal template never renders it' => [TemplateOverrideType::PluginTemplateSkipped, Severity::Error];
        yield 'parent() inside a condition depends on runtime data' => [TemplateOverrideType::ConditionalParent, Severity::Warning];
        yield 'block re-declared inside a condition depends on runtime data' => [TemplateOverrideType::ConditionalBlock, Severity::Warning];
        yield 'extends of a variable depends on runtime data' => [TemplateOverrideType::DynamicExtends, Severity::Warning];
        yield 'template the check could not parse is not known to be broken' => [TemplateOverrideType::Unreadable, Severity::Unknown];
    }
}
