<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util;

use PHPUnit\Framework\TestCase;
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
    public function testGetLocaleCodeFromDefaultContext(): void
    {
        $localeCodeProvider = new LocaleCodeProvider(new LanguageRepoMock());
        $iso = $localeCodeProvider->getLocaleCodeFromContext(Context::createDefaultContext());

        static::assertSame(LanguageRepoMock::LOCALE_CODE, $iso);
    }
}
