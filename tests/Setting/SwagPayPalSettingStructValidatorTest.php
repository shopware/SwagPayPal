<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Setting;

use PHPUnit\Framework\TestCase;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Setting\SwagPayPalSettingStructValidator;

class SwagPayPalSettingStructValidatorTest extends TestCase
{
    public function testValidateWithValidSettings(): void
    {
        $settings = new SwagPayPalSettingStruct();
        $settings->setClientId('SomeClientId');
        $settings->setClientSecret('SomeClientSecret');

        // If the Settings struct is invalid an exception gets thrown means if we can assert after this statement everything is fine
        SwagPayPalSettingStructValidator::validate($settings);
        static::assertTrue(true);
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
}
