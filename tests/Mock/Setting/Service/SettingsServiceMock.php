<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Mock\Setting\Service;

use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;

class SettingsServiceMock implements SettingsServiceInterface
{
    /**
     * @var SwagPayPalSettingGeneralStruct
     */
    private $settings;

    public function __construct(SwagPayPalSettingGeneralStruct $settings)
    {
        $this->settings = $settings;
    }

    public function getSettings(?string $salesChannelId = null): SwagPayPalSettingGeneralStruct
    {
        return $this->settings;
    }

    public function updateSettings(array $settings, ?string $salesChannelId = null): void
    {
        $this->settings->assign($settings);
    }
}
