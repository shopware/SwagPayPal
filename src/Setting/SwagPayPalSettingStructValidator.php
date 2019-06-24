<?php declare(strict_types=1);

namespace Swag\PayPal\Setting;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class SwagPayPalSettingStructValidator
{
    /**
     * @throws PayPalSettingsInvalidException
     */
    public static function validate(SwagPayPalSettingStruct $generalStruct): void
    {
        try {
            $generalStruct->getClientId();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientId');
        }

        try {
            $generalStruct->getClientSecret();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientSecret');
        }
    }
}
