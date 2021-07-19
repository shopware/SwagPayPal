<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Setting\SwagPayPalSettingStructValidator;

/**
 * @deprecated tag:v4.0.0 - will be removed. Use Shopware\Core\System\SystemConfig\SystemConfigService directly instead.
 */
class SettingsService implements SettingsServiceInterface
{
    public const SYSTEM_CONFIG_DOMAIN = Settings::SYSTEM_CONFIG_DOMAIN;

    private SystemConfigService $systemConfigService;

    private LoggerInterface $logger;

    public function __construct(SystemConfigService $systemConfigService, LoggerInterface $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    public function getSettings(?string $salesChannelId = null, bool $inherited = true): SwagPayPalSettingStruct
    {
        $values = $this->systemConfigService->getDomain(
            Settings::SYSTEM_CONFIG_DOMAIN,
            $salesChannelId,
            $inherited
        );

        $propertyValuePairs = [];

        /** @var string $key */
        foreach ($values as $key => $value) {
            $property = (string) \mb_substr($key, \mb_strlen(Settings::SYSTEM_CONFIG_DOMAIN));
            if ($property === '') {
                continue;
            }
            $propertyValuePairs[$property] = $value;
        }

        $settingsEntity = new SwagPayPalSettingStruct();
        $settingsEntity->assign($propertyValuePairs);
        if ($inherited) {
            try {
                SwagPayPalSettingStructValidator::validate($settingsEntity);
            } catch (PayPalSettingsInvalidException $exception) {
                $this->logger->info($exception->getMessage(), ['error' => $exception]);

                throw $exception;
            }
        }

        return $settingsEntity;
    }

    public function updateSettings(array $settings, ?string $salesChannelId = null): void
    {
        foreach ($settings as $key => $value) {
            $this->systemConfigService->set(
                Settings::SYSTEM_CONFIG_DOMAIN . $key,
                $value,
                $salesChannelId
            );
        }
    }
}
