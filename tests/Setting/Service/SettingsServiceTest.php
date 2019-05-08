<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Swag\PayPal\Setting\Exception\PayPalSettingsNotFoundException;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralDefinition;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralEntity;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;

class SettingsServiceTest extends TestCase
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

    private function createSettingsProvider(): SettingsService
    {
        return new SettingsService(
            new DefinitionInstanceRegistryMock([], new DIContainerMock()),
            new SwagPayPalSettingGeneralDefinition()
        );
    }
}
