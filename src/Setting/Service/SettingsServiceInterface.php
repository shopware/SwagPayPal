<?php declare(strict_types=1);

namespace Swag\PayPal\Setting\Service;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;

interface SettingsServiceInterface
{
    /**
     * @throws PayPalSettingsInvalidException
     */
    public function getSettings(?string $salesChannelId = null): SwagPayPalSettingStruct;

    public function updateSettings(array $settings, ?string $salesChannelId = null): void;
}
