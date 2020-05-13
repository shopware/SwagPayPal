<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;

class SwagPayPalSettingStructValidator
{
    /**
     * @throws PayPalSettingsInvalidException
     */
    public static function validate(SwagPayPalSettingStruct $generalStruct): void
    {
        if ($generalStruct->getSandbox()) {
            self::validateSandboxCredentials($generalStruct);
        } else {
            self::validateLiveCredentials($generalStruct);
        }
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected static function validateSandboxCredentials(SwagPayPalSettingStruct $generalStruct): void
    {
        try {
            $clientIdSandbox = $generalStruct->getClientIdSandbox();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientIdSandbox');
        }

        if ($clientIdSandbox === '') {
            throw new PayPalSettingsInvalidException('ClientIdSandbox');
        }

        try {
            $clientSecretSandbox = $generalStruct->getClientSecretSandbox();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientSecretSandbox');
        }

        if ($clientSecretSandbox === '') {
            throw new PayPalSettingsInvalidException('ClientSecretSandbox');
        }
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    protected static function validateLiveCredentials(SwagPayPalSettingStruct $generalStruct): void
    {
        try {
            $clientId = $generalStruct->getClientId();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientId');
        }

        if ($clientId === '') {
            throw new PayPalSettingsInvalidException('ClientId');
        }

        try {
            $clientSecret = $generalStruct->getClientSecret();
        } catch (\TypeError $error) {
            throw new PayPalSettingsInvalidException('ClientSecret');
        }

        if ($clientSecret === '') {
            throw new PayPalSettingsInvalidException('ClientSecret');
        }
    }
}
