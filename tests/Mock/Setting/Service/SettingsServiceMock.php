<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Setting\Service;

use Swag\PayPal\Setting\Exception\PayPalSettingsNotFoundException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;

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
        if ($this->settings === null) {
            throw new PayPalSettingsNotFoundException();
        }

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
