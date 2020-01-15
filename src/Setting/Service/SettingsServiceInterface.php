<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Swag\PayPal\Setting\SwagPayPalSettingStruct;

interface SettingsServiceInterface
{
    public function getSettings(?string $salesChannelId = null): SwagPayPalSettingStruct;

    public function updateSettings(array $settings, ?string $salesChannelId = null): void;
}
