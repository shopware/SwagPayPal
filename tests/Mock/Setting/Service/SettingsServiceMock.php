<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Mock\Setting\Service;

use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Setting\SwagPayPalSettingStructValidator;

class SettingsServiceMock implements SettingsServiceInterface
{
    /**
     * @var SwagPayPalSettingStruct|null
     */
    private $settings;

    public function __construct(?SwagPayPalSettingStruct $settings = null)
    {
        $this->settings = $settings;
    }

    public function getSettings(?string $salesChannelId = null, bool $inherited = true): SwagPayPalSettingStruct
    {
        if ($this->settings === null) {
            throw new PayPalSettingsInvalidException('clientId');
        }

        SwagPayPalSettingStructValidator::validate($this->settings);

        return $this->settings;
    }

    public function updateSettings(array $settings, ?string $salesChannelId = null): void
    {
        if ($this->settings === null) {
            $this->settings = new SwagPayPalSettingStruct();
        }
        $this->settings->assign($settings);
    }
}
