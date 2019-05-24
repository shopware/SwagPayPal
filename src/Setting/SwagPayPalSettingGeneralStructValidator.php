<?php declare(strict_types=1);

namespace Swag\PayPal\Setting;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class SwagPayPalSettingGeneralStructValidator
{
    /**
     * @throws PayPalSettingsInvalidException
     */
    public static function validate(SwagPayPalSettingGeneralStruct $generalStruct): void
    {
        try {
            $generalStruct->getClientId();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientId');
        }

        try {
            $generalStruct->getClientSecret();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientSecre');
        }
    }
}
