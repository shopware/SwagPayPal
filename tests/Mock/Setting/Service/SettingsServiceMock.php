<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Setting\Service;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStructValidator;

class SettingsServiceMock implements SettingsServiceInterface
{
    /**
     * @var SwagPayPalSettingGeneralStruct|null
     */
    private $settings;

    public function __construct(?SwagPayPalSettingGeneralStruct $settings = null)
    {
        $this->settings = $settings;
    }

    public function getSettings(?string $salesChannelId = null): SwagPayPalSettingGeneralStruct
    {
        if (!$this->settings) {
            throw new PayPalSettingsInvalidException('clientId');
        }

        SwagPayPalSettingGeneralStructValidator::validate($this->settings);

        return $this->settings;
    }

    public function updateSettings(array $settings, ?string $salesChannelId = null): void
    {
        if ($this->settings === null) {
            $this->settings = new SwagPayPalSettingGeneralStruct();
        }
        $this->settings->assign($settings);
    }
}
