<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Diagnostics;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;

/**
 * Each value of a check's result needs an Administration snippet.
 *
 * @internal
 */
#[Package('checkout')]
class AdministrationSnippetTest extends TestCase
{
    private const SNIPPET_DIRECTORY = __DIR__ . '/../../src/Resources/app/administration/src/module/swag-paypal-settings/snippet/';

    #[DataProvider('languageProvider')]
    public function testEveryReachabilityErrorHasATitleAndDescription(string $language): void
    {
        $this->assertTitleAnd($this->getSnippets($language, 'reachability', 'errors'), ReachabilityError::cases());
    }

    #[DataProvider('languageProvider')]
    public function testReachableHasATitle(string $language): void
    {
        $snippets = \json_decode((string) \file_get_contents(self::SNIPPET_DIRECTORY . $language . '.json'), true, flags: \JSON_THROW_ON_ERROR);
        static::assertIsArray($snippets);

        $reachable = $snippets['swag-paypal-settings']['diagnostics']['reachability']['reachable'] ?? null;
        static::assertIsArray($reachable);
        static::assertSame(['title'], \array_keys($reachable));
    }

    public static function languageProvider(): \Generator
    {
        yield 'English' => ['en'];
        yield 'German' => ['de'];
    }

    /**
     * @param array<mixed> $snippets
     * @param list<\BackedEnum> $cases
     */
    private function assertTitleAnd(array $snippets, array $cases, string $text = 'description'): void
    {
        foreach ($cases as $case) {
            static::assertIsArray($snippets[$case->value] ?? null, (string) $case->value);
            static::assertArrayHasKey('title', $snippets[$case->value], (string) $case->value);
            static::assertArrayHasKey($text, $snippets[$case->value], (string) $case->value);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getSnippets(string $language, string $check, string $group): array
    {
        $snippets = \json_decode((string) \file_get_contents(self::SNIPPET_DIRECTORY . $language . '.json'), true, flags: \JSON_THROW_ON_ERROR);
        static::assertIsArray($snippets);

        $group = $snippets['swag-paypal-settings']['diagnostics'][$check][$group] ?? null;
        static::assertIsArray($group);

        return $group;
    }
}
