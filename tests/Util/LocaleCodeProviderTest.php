<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\MockObject\MockObject;
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
    private LoggerInterface&MockObject $logger;

    private LocaleCodeProvider $provider;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->provider = new LocaleCodeProvider(
            new LanguageRepoMock(),
            $this->logger
        );
    }

    public function testGetLocaleCodeFromDefaultContext(): void
    {
        $iso = $this->provider->getLocaleCodeFromContext(Context::createDefaultContext());

        static::assertSame(LanguageRepoMock::LOCALE_CODE, $iso);
    }

    public function testGetFormattedLocaleCodeWithSupportedLocale(): void
    {
        $this->logger->expects(static::never())->method('notice');

        $locale = $this->provider->getFormattedLocaleCode('en_US');

        static::assertSame('en_US', $locale);
    }

    public function testGetFormattedLocaleCodeWithUnsupportedLocale(): void
    {
        $this->logger->expects(static::once())
            ->method('notice')
            ->with('PayPal does not support locale code cch-ZZ. Switched to default en_GB.');

        $locale = $this->provider->getFormattedLocaleCode('cch-ZZ');

        static::assertSame('en_GB', $locale);
    }

    public function testGetFormattedLocaleCodeWithFallbackLocale(): void
    {
        $this->logger->expects(static::once())
            ->method('notice')
            ->with('PayPal does not support locale code en_ZA. Switched to en_US.');

        $locale = $this->provider->getFormattedLocaleCode('en_ZA');

        static::assertSame('en_US', $locale);
    }
}
