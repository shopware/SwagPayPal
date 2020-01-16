<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Setting\SwagPayPalSettingStructValidator;

class SettingsService implements SettingsServiceInterface
{
    public const SYSTEM_CONFIG_DOMAIN = 'SwagPayPal.settings.';

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    public function getSettings(?string $salesChannelId = null): SwagPayPalSettingStruct
    {
        $values = $this->systemConfigService->getDomain(
            self::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            true
        );

        $propertyValuePairs = [];

        /** @var string $key */
        foreach ($values as $key => $value) {
            $property = (string) mb_substr($key, \mb_strlen(self::SYSTEM_CONFIG_DOMAIN));
            if ($property === '') {
                continue;
            }
            $propertyValuePairs[$property] = $value;
        }

        $settingsEntity = new SwagPayPalSettingStruct();
        $settingsEntity->assign($propertyValuePairs);
        SwagPayPalSettingStructValidator::validate($settingsEntity);

        return $settingsEntity;
    }

    public function updateSettings(array $settings, ?string $salesChannelId = null): void
    {
        foreach ($settings as $key => $value) {
            $this->systemConfigService->set(
                self::SYSTEM_CONFIG_DOMAIN . $key,
                $value,
                $salesChannelId
            );
        }
    }
}
