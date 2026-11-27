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
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackFindingType;
use Swag\PayPal\Diagnostics\ExpressCallback\ExpressCallbackStatus;
use Swag\PayPal\Diagnostics\Reachability\ReachabilityError;
use Swag\PayPal\Diagnostics\Twig\Override\TemplateOverrideType;
use Swag\PayPal\Diagnostics\Twig\TemplateArea;
use Swag\PayPal\Diagnostics\Webhook\WebhookFindingType;
use Symfony\Component\Finder\Finder;

/**
 * Each value of a check's result needs an Administration snippet.
 *
 * @internal
 */
#[Package('checkout')]
class AdministrationSnippetTest extends TestCase
{
    private const SNIPPET_DIRECTORY = __DIR__ . '/../../src/Resources/app/administration/src/module/swag-paypal-settings/snippet/';

    private const VIEWS_DIRECTORY = __DIR__ . '/../../src/Resources/views/';

    #[DataProvider('languageProvider')]
    public function testEveryMarkedFeatureHasALabel(string $language): void
    {
        $features = [];
        foreach ((new Finder())->files()->in(self::VIEWS_DIRECTORY)->name('*.twig') as $file) {
            \preg_match_all('/@diagnostics\s+([a-z0-9_]+)/', $file->getContents(), $matches);
            $features = [...$features, ...$matches[1]];
        }
        static::assertNotSame([], $features);

        $labels = $this->getSnippets($language, 'twig', 'features');

        static::assertSame([], \array_values(\array_diff(\array_unique($features), \array_keys($labels))));
    }

    #[DataProvider('languageProvider')]
    public function testEveryOverrideTypeHasATitleAndDescription(string $language): void
    {
        $this->assertTitleAnd($this->getSnippets($language, 'twig', 'types'), TemplateOverrideType::cases());
    }

    #[DataProvider('languageProvider')]
    public function testEveryAreaHasItsTexts(string $language): void
    {
        $areas = $this->getSnippets($language, 'twig', 'areas');

        foreach (TemplateArea::cases() as $area) {
            static::assertIsArray($areas[$area->value] ?? null, $area->value);
            static::assertSame(['title', 'notApplicable', 'noOverrides'], \array_keys($areas[$area->value]), $area->value);
        }
    }

    #[DataProvider('languageProvider')]
    public function testEveryExpressCallbackStatusButActiveHasATitleAndSummary(string $language): void
    {
        $statuses = \array_values(\array_filter(ExpressCallbackStatus::cases(), static fn (ExpressCallbackStatus $status) => $status !== ExpressCallbackStatus::Active));

        $this->assertTitleAnd($this->getSnippets($language, 'expressCallback', 'status'), $statuses, 'summary');
    }

    #[DataProvider('languageProvider')]
    public function testEveryExpressCallbackFindingHasATitleAndSummary(string $language): void
    {
        $this->assertTitleAnd($this->getSnippets($language, 'expressCallback', 'findings'), ExpressCallbackFindingType::cases(), 'summary');
    }

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

    #[DataProvider('languageProvider')]
    public function testEveryWebhookFindingHasATitleAndSummary(string $language): void
    {
        $this->assertTitleAnd($this->getSnippets($language, 'webhook', 'findings'), WebhookFindingType::cases(), 'summary');
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
