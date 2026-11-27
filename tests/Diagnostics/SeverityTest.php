<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Severity;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Severity::class)]
class SeverityTest extends TestCase
{
    public function testComparesBySeverity(): void
    {
        $severities = [Severity::Unknown, Severity::Notice, Severity::Error, Severity::Warning, Severity::Error];

        \usort($severities, static fn (Severity $a, Severity $b) => $a->compare($b));

        static::assertSame([Severity::Error, Severity::Error, Severity::Warning, Severity::Notice, Severity::Unknown], $severities);
        static::assertSame(0, Severity::Warning->compare(Severity::Warning));
    }

    public function testValues(): void
    {
        static::assertSame(['error', 'warning', 'notice', 'unknown'], \array_column(Severity::cases(), 'value'));
    }
}
