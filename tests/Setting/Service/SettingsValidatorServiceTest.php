<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;

/**
 * @internal
 */
#[Package('checkout')]
class SettingsValidatorServiceTest extends TestCase
{
    use ServicesTrait;

    public function testValidateWithValidLiveSettings(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID, 'SomeClientId');
        $systemSettings->set(Settings::CLIENT_SECRET, 'SomeClientSecret');

        // If the settings are invalid, an exception gets thrown.
        // That means able to assert after this statement, everything is fine.
        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $validationService->validate();
        $validationService->validate(TestDefaults::SALES_CHANNEL);
    }

    public function testValidateWithValidLiveDistinctSettings(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID, 'SomeClientId', TestDefaults::SALES_CHANNEL);
        $systemSettings->set(Settings::CLIENT_SECRET, 'SomeClientSecret', TestDefaults::SALES_CHANNEL);

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $validationService->validate(TestDefaults::SALES_CHANNEL);
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }

    public function testValidateWithValidSandboxSettings(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID_SANDBOX, 'SomeClientId');
        $systemSettings->set(Settings::CLIENT_SECRET_SANDBOX, 'SomeClientSecret');
        $systemSettings->set(Settings::SANDBOX, true);

        // If the settings are invalid, an exception gets thrown.
        // That means able to assert after this statement, everything is fine.
        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $validationService->validate();
        $validationService->validate(TestDefaults::SALES_CHANNEL);
    }

    public function testValidateWithValidSandboxSettingsButSandboxDisabled(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID_SANDBOX, 'SomeClientId');
        $systemSettings->set(Settings::CLIENT_SECRET_SANDBOX, 'SomeClientSecret');
        $systemSettings->set(Settings::SANDBOX, false);

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }

    public function testValidateWithValidLiveSettingsButSandboxEnabled(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID, 'SomeClientId');
        $systemSettings->set(Settings::CLIENT_SECRET, 'SomeClientSecret');
        $systemSettings->set(Settings::SANDBOX, true);

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }

    public function testValidateWithoutClientSecretThrowsException(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID, 'SomeClientId');

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }

    public function testValidateWithoutClientIdThrowsException(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }

    public function testValidateWithoutClientSecretSandboxThrowsException(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::CLIENT_ID_SANDBOX, 'SomeClientId');
        $systemSettings->set(Settings::SANDBOX, true);

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }

    public function testValidateWithoutClientIdSandboxThrowsException(): void
    {
        $systemSettings = $this->createSystemConfigServiceMock();
        $systemSettings->set(Settings::SANDBOX, true);

        $validationService = new SettingsValidationService($systemSettings, new NullLogger());
        $this->expectException(PayPalSettingsInvalidException::class);
        $validationService->validate();
    }
}
