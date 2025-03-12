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

        $crawler->filter('table tbody tr')->each(function (Crawler $row) use (&$locales): void {
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
            $localeString = '';
            foreach ($localeOptions as $locale) {
                $localeString .= $this->getLocaleString($locale);
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

    private function getLocaleString(array $locale): string
    {
        return <<<EOD
            {$locale[self::PRIORITY]} => '{$locale[self::LOCALE_CODE_KEY]}',

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
