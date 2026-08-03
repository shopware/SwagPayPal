<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\SupportedLocales;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(SupportedLocales::class)]
class SupportedLocalesTest extends TestCase
{
    /**
     * The locale codes of a region must be a list ordered by ascending language support
     * priority, because {@see LocaleCodeProvider::getFormattedLocaleCode()} falls back to
     * the first entry of a region. PayPal renumbered the priority column of its locale
     * table from 0-based to 1-based once, which turned every list into a 1-based array and
     * silently broke that fallback for all regions.
     *
     * @param mixed $localeCodes the generated value is deliberately untyped, as its shape
     *                           is what this test has to verify
     */
    #[DataProvider('regionProvider')]
    public function testLocaleCodesAreZeroBasedList(string $countryCode, $localeCodes): void
    {
        static::assertIsArray($localeCodes);
        static::assertSame(
            \range(0, \count($localeCodes) - 1),
            \array_keys($localeCodes),
            \sprintf('Locale codes of region "%s" are not a 0-based list', $countryCode)
        );
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function regionProvider(): iterable
    {
        foreach (SupportedLocales::LOCALES as $countryCode => $localeCodes) {
            yield $countryCode => [$countryCode, $localeCodes];
        }
    }
}
