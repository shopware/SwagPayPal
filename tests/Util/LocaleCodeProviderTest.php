<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Test\Mock\Repositories\LanguageRepoMock;
use Swag\PayPal\Util\LocaleCodeProvider;

/**
 * @internal
 */
#[Package('checkout')]
class LocaleCodeProviderTest extends TestCase
{
    private LocaleCodeProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new LocaleCodeProvider(
            new LanguageRepoMock(),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testGetLocaleCodeFromDefaultContext(): void
    {
        $iso = $this->provider->getLocaleCodeFromContext(Context::createDefaultContext());

        static::assertSame(LanguageRepoMock::LOCALE_CODE, $iso);
    }

    public function testGetDefaultLocale(): void
    {
        $locale = $this->provider->getFormattedLocaleCode('cch-NG');

        static::assertSame('en_GB', $locale);
    }
}
