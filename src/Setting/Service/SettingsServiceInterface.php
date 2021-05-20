<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;

/**
 * @deprecated tag:v4.0.0 - will be removed. Use Shopware\Core\System\SystemConfig\SystemConfigService directly instead.
 */
interface SettingsServiceInterface
{
    /**
     * @throws PayPalSettingsInvalidException
     */
    public function getSettings(?string $salesChannelId = null, bool $inherited = true): SwagPayPalSettingStruct;

    public function updateSettings(array $settings, ?string $salesChannelId = null): void;
}
