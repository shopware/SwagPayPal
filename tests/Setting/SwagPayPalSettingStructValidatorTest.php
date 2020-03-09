<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Setting\SwagPayPalSettingStructValidator;

class SwagPayPalSettingStructValidatorTest extends TestCase
{
    public function testValidateWithValidLiveSettings(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('SomeClientId');
        $settings->setClientSecret('SomeClientSecret');

        // If the Settings struct is invalid, an exception gets thrown.
        // That means able to assert after this statement, everything is fine.
        SwagPayPalSettingStructValidator::validate($settings);
        static::assertTrue(true);
    }

    public function testValidateWithValidSandboxSettings(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientIdSandbox('SomeClientId');
        $settings->setClientSecretSandbox('SomeClientSecret');
        $settings->setSandbox(true);

        // If the Settings struct is invalid, an exception gets thrown.
        // That means able to assert after this statement, everything is fine.
        SwagPayPalSettingStructValidator::validate($settings);
        static::assertTrue(true);
    }

    public function testValidateWithValidSandboxSettingsButSandboxDisabled(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientIdSandbox('SomeClientId');
        $settings->setClientSecretSandbox('SomeClientSecret');
        $settings->setSandbox(false);

        $this->expectException(PayPalSettingsInvalidException::class);
        SwagPayPalSettingStructValidator::validate($settings);
    }

    public function testValidateWithValidLiveSettingsButSandboxEnabled(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('SomeClientId');
        $settings->setClientSecret('SomeClientSecret');
        $settings->setSandbox(true);

        $this->expectException(PayPalSettingsInvalidException::class);
        SwagPayPalSettingStructValidator::validate($settings);
    }

    public function testValidateWithoutClientSecretThrowsException(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('SomeClientId');

        $this->expectException(PayPalSettingsInvalidException::class);
        SwagPayPalSettingStructValidator::validate($settings);
    }

    public function testValidateWithoutClientIdThrowsException(): void
    {
        $settings = new SwagPayPalSettingStruct();

        $this->expectException(PayPalSettingsInvalidException::class);
        SwagPayPalSettingStructValidator::validate($settings);
    }

    public function testValidateWithoutClientSecretSandboxThrowsException(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientIdSandbox('SomeClientId');
        $settings->setSandbox(true);

        $this->expectException(PayPalSettingsInvalidException::class);
        SwagPayPalSettingStructValidator::validate($settings);
    }

    public function testValidateWithoutClientIdSandboxThrowsException(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setSandbox(true);

        $this->expectException(PayPalSettingsInvalidException::class);
        SwagPayPalSettingStructValidator::validate($settings);
    }
}
