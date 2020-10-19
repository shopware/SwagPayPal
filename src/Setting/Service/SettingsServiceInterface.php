<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;

interface SettingsServiceInterface
{
    /**
     * @throws PayPalSettingsInvalidException
     *
     * @deprecated tag:v2.0.0 - The parameter $inherited will be added
     */
    public function getSettings(?string $salesChannelId = null/* , bool $inherited = true */): SwagPayPalSettingStruct;

    public function updateSettings(array $settings, ?string $salesChannelId = null): void;
}
