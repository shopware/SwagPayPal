<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagPayPal\Setting\Exception\PayPalSettingsNotFoundException;
use SwagPayPal\Setting\Service\SettingsProvider;
use SwagPayPal\Setting\SwagPayPalSettingGeneralEntity;
use SwagPayPal\Test\Mock\Repositories\SwagPayPalSettingGeneralRepoMock;

class SettingsProviderTest extends TestCase
{
    public const THROW_EXCEPTION = 'throwsExceptionBecauseOfNoSettings';

    public function testGetSettings(): void
    {
        $settingsProvider = $this->createSettingsProvider();

        $context = Context::createDefaultContext();
        $settings = $settingsProvider->getSettings($context);

        static::assertInstanceOf(SwagPayPalSettingGeneralEntity::class, $settings);
        static::assertInstanceOf(\DateTime::class, $settings->getCreatedAt());
        static::assertInstanceOf(\DateTime::class, $settings->getUpdatedAt());
    }

    public function testGetSettingsThrowsException(): void
    {
        $settingsProvider = $this->createSettingsProvider();

        $context = Context::createDefaultContext();
        $context->addExtension(self::THROW_EXCEPTION, new Entity());

        $this->expectException(PayPalSettingsNotFoundException::class);
        $this->expectExceptionMessage('PayPal settings not found');
        $settingsProvider->getSettings($context);
    }

    private function createSettingsProvider(): SettingsProvider
    {
        return new SettingsProvider(new SwagPayPalSettingGeneralRepoMock());
    }
}
