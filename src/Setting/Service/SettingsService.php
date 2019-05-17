<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Exception\PayPalSettingsNotFoundException;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralStruct;

class SettingsService implements SettingsServiceInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @throws PayPalSettingsNotFoundException
     */
    public function getSettings(?string $salesChannelId = null): SwagPayPalSettingGeneralStruct
    {
        $prefix = 'SwagPayPal.settings.';
        $values = $this->systemConfigService->getDomain($prefix, $salesChannelId, true);

        $propertyValuePairs = [];
        foreach ($values as $key => $value) {
            $property = substr($key, strlen($prefix));
            $propertyValuePairs[$property] = $value;
        }

        $settingsEntity = new SwagPayPalSettingGeneralStruct();
        $settingsEntity->assign($propertyValuePairs);

        return $settingsEntity;
    }

    public function updateSettings(array $settings, ?string $salesChannelId = null): void
    {
        foreach ($settings as $key => $value) {
            $this->systemConfigService->set(
                'SwagPayPal.settings.' . $key,
                $value,
                $salesChannelId
            );
        }
    }
}
