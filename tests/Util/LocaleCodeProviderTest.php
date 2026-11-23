<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Util\LocaleCodeProvider;
use Swag\PayPal\Util\Log\Log;

/**
 * @internal
 */
#[Package('checkout')]
class LocaleCodeProviderTest extends TestCase
{
    use PayPalLogTrait;

    private LocaleCodeProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new LocaleCodeProvider(
            new LanguageRepoMock(),
            $this->createPayPalLogger()
        );
    }

    public function testGetLocaleCodeFromDefaultContext(): void
    {
        $iso = $this->provider->getLocaleCodeFromContext(Context::createDefaultContext());

        static::assertSame(LanguageRepoMock::LOCALE_CODE, $iso);
    }

    public function testGetFormattedLocaleCodeWithSupportedLocale(): void
    {
        $locale = $this->provider->getFormattedLocaleCode('en_US');

        static::assertSame('en_US', $locale);
        static::assertSame([], $this->getPayPalLogRecords());
    }

    public function testGetFormattedLocaleCodeWithUnsupportedLocale(): void
    {
        $locale = $this->provider->getFormattedLocaleCode('cch-ZZ');

        static::assertSame('en_GB', $locale);
        $this->assertPayPalLogged(
            null,
            Level::Debug,
            [Log::LOCALE_CODE => 'cch-ZZ', Log::FALLBACK_LOCALE_CODE => 'en_GB'],
            'PayPal doesn\'t support locale {localeCode}, used {fallbackLocaleCode} instead',
        );
    }

    public function testGetFormattedLocaleCodeWithFallbackLocale(): void
    {
        $locale = $this->provider->getFormattedLocaleCode('en_ZA');

        static::assertSame('en_US', $locale);
        $this->assertPayPalLogged(
            null,
            Level::Debug,
            [Log::LOCALE_CODE => 'en_ZA', Log::FALLBACK_LOCALE_CODE => 'en_US'],
            'PayPal doesn\'t support locale {localeCode}, used {fallbackLocaleCode} instead',
        );
    }
}
