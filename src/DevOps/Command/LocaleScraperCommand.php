<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\DevOps\Command;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'swag:paypal:scrape:locales',
    description: 'Scrapes the PayPal developer website for locales and updates "Swag\PayPal\Util\PaypalLocales"',
)]
#[Package('checkout')]
class LocaleScraperCommand extends Command
{
    public const REGION_KEY = 'region';
    public const LOCALE_CODE_KEY = 'locale_code';
    public const PRIORITY = 'priority';
    private const PAYPAL_LOCALES_PAGE = 'https://developer.paypal.com/reference/locale-codes/';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $client = HttpClient::create();
        $response = $client->request('GET', self::PAYPAL_LOCALES_PAGE);
        $html = $response->getContent();

        $crawler = new Crawler($html);
        $locales = [];

        $crawler->filter('table tbody tr')->each(static function (Crawler $row) use (&$locales): void {
            $columns = $row->filter('td');
            if ($columns->count() < 3) {
                return;
            }

            $countryCode = trim($columns->eq(1)->text());

            $locales[$countryCode][] = [
                self::PRIORITY => trim($columns->eq(2)->text()),
                self::LOCALE_CODE_KEY => trim($columns->eq(3)->text()),
            ];
        });

        $localesClassContent = '';
        foreach ($locales as $countryCode => $localeOptions) {
            \usort($localeOptions, static fn (array $a, array $b): int => (int) $a[self::PRIORITY] <=> (int) $b[self::PRIORITY]);

            $localeString = '';
            foreach ($localeOptions as $position => $locale) {
                $localeString .= $this->getLocaleString($position, $locale[self::LOCALE_CODE_KEY]);
            }
            $localesClassContent .= \sprintf(
                "        '%s' => [\n%s\n        ],\n",
                $countryCode,
                trim($localeString, "\n")
            );
        }

        $localesClass = \sprintf($this->getClassTemplate(), self::PAYPAL_LOCALES_PAGE, \trim($localesClassContent, "\n"));

        $localesClassPath = __DIR__ . '/../../Util/SupportedLocales.php';
        $result = \file_put_contents($localesClassPath, $localesClass, \LOCK_EX);
        if ($result === false) {
            throw new \RuntimeException(\sprintf('File "%s" could not be written', $localesClassPath));
        }

        return 0;
    }

    /**
     * The key is the position within the region, not the scraped priority. PayPal changed
     * that column from 0-based to 1-based numbering once, which broke every
     * `SupportedLocales::LOCALES[$countryCode][0]` lookup.
     */
    private function getLocaleString(int $position, string $localeCode): string
    {
        return <<<EOD
            {$position} => '{$localeCode}',

EOD;
    }

    private function getClassTemplate(): string
    {
        return <<<EOD
<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util;

use Shopware\Core\Framework\Log\Package;

/**
 * @url %s
 */
#[Package('checkout')]
final class SupportedLocales
{
    public const LOCALES = [
%s
    ];
}

EOD;
    }
}
