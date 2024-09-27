<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Setting\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Settings;

#[Package('checkout')]
class SettingsValidationService implements SettingsValidationServiceInterface
{
    private SystemConfigService $systemConfigService;

    private LoggerInterface $logger;

    /**
     * @internal
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        LoggerInterface $logger,
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * @throws PayPalSettingsInvalidException
     */
    public function validate(?string $salesChannelId = null): void
    {
        $settings = $this->fetchSettings($salesChannelId);

        $missingSetting = $this->checkForMissingSetting($settings);

        if ($missingSetting === null) {
            return;
        }

        $exception = new PayPalSettingsInvalidException($missingSetting);

        $this->logger->info($exception->getMessage(), ['error' => $exception]);

        throw $exception;
    }

    public function checkForMissingSetting(array $settings): ?string
    {
        $clientIdKey = $settings[Settings::SANDBOX] ? Settings::CLIENT_ID_SANDBOX : Settings::CLIENT_ID;
        if (($settings[$clientIdKey] ?? '') === '') {
            return $clientIdKey;
        }

        $clientSecretKey = $settings[Settings::SANDBOX] ? Settings::CLIENT_SECRET_SANDBOX : Settings::CLIENT_SECRET;
        if (($settings[$clientSecretKey] ?? '') === '') {
            return $clientSecretKey;
        }

        return null;
    }

    private function fetchSettings(?string $salesChannelId): array
    {
        return [
            Settings::CLIENT_ID => $this->systemConfigService->getString(Settings::CLIENT_ID, $salesChannelId),
            Settings::CLIENT_SECRET => $this->systemConfigService->getString(Settings::CLIENT_SECRET, $salesChannelId),
            Settings::CLIENT_ID_SANDBOX => $this->systemConfigService->getString(Settings::CLIENT_ID_SANDBOX, $salesChannelId),
            Settings::CLIENT_SECRET_SANDBOX => $this->systemConfigService->getString(Settings::CLIENT_SECRET_SANDBOX, $salesChannelId),
            Settings::SANDBOX => $this->systemConfigService->getBool(Settings::SANDBOX, $salesChannelId),
        ];
    }
}
