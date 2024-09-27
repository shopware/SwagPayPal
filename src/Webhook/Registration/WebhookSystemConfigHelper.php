<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\WebhookServiceInterface;

#[Package('checkout')]
class WebhookSystemConfigHelper
{
    private const WEBHOOK_KEYS = [
        Settings::CLIENT_ID,
        Settings::CLIENT_SECRET,
        Settings::CLIENT_ID_SANDBOX,
        Settings::CLIENT_SECRET_SANDBOX,
        Settings::SANDBOX,
        Settings::WEBHOOK_ID,
    ];

    private LoggerInterface $logger;

    private WebhookServiceInterface $webhookService;

    private SystemConfigService $systemConfigService;

    private SettingsValidationServiceInterface $settingsValidationService;

    /**
     * @internal
     */
    public function __construct(
        LoggerInterface $logger,
        WebhookServiceInterface $webhookService,
        SystemConfigService $systemConfigService,
        SettingsValidationServiceInterface $settingsValidationService,
    ) {
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->systemConfigService = $systemConfigService;
        $this->settingsValidationService = $settingsValidationService;
    }

    /**
     * @param array<string, array<string, mixed>> $newData
     *
     * @return \Throwable[]
     */
    public function checkWebhookBefore(array $newData): array
    {
        $errors = [];

        foreach ($newData as $salesChannelId => $newSettings) {
            if ($salesChannelId === 'null') {
                $salesChannelId = null;
            }

            $oldDistinctSettings = $this->fetchSettings($salesChannelId);
            if (empty($oldDistinctSettings)) {
                // Sales Channel previously had no own configuration
                continue;
            }

            $oldActualSettings = $this->fetchSettings($salesChannelId, true);
            if ($this->settingsValidationService->checkForMissingSetting($oldActualSettings) !== null) {
                // this sales channel has no valid setting
                continue;
            }

            if (!$this->configHasChangedSettings($newSettings, $oldActualSettings)) {
                // No writing of new credentials in this Sales Channel
                continue;
            }

            // since credentials will be changed: try to deregister with old credentials
            try {
                $this->webhookService->deregisterWebhook($salesChannelId);
            } catch (\Throwable $e) {
                $errors[] = $e;
                $this->logger->error($e->getMessage(), ['error' => $e]);
            }
        }

        return $errors;
    }

    /**
     * @param string[] $salesChannelIds
     *
     * @return \Throwable[]
     */
    public function checkWebhookAfter(array $salesChannelIds): array
    {
        $errors = [];

        foreach ($salesChannelIds as $salesChannelId) {
            if ($salesChannelId === 'null') {
                $salesChannelId = null;
            }

            $newSettings = $this->fetchSettings($salesChannelId);
            if (empty(\array_filter($newSettings))) {
                // has no own valid configuration
                continue;
            }

            $newSettings[Settings::SANDBOX] = $this->systemConfigService->get(Settings::SANDBOX, $salesChannelId);
            if ($this->settingsValidationService->checkForMissingSetting($newSettings) !== null) {
                // this sales channel has no valid setting
                continue;
            }

            try {
                $this->webhookService->registerWebhook($salesChannelId);
            } catch (\Throwable $e) {
                $errors[] = $e;
                $this->logger->error($e->getMessage(), ['error' => $e]);
            }
        }

        return $errors;
    }

    private function fetchSettings(?string $salesChannelId, bool $inherit = false): array
    {
        $settings = [];
        foreach (self::WEBHOOK_KEYS as $key) {
            $value = $this->systemConfigService->get($key, $salesChannelId);

            if (!$inherit && $salesChannelId !== null && $value === $this->systemConfigService->get($key)) {
                continue;
            }

            $settings[$key] = $value;
        }

        return $settings;
    }

    private function configHasChangedSettings(array $newSettings, array $oldSettings): bool
    {
        return !empty(\array_diff_assoc($this->filterSettings($newSettings), $oldSettings));
    }

    /**
     * @param array<string, mixed> $kvs
     */
    private function filterSettings(array $kvs): array
    {
        return \array_filter($kvs, static function (string $key) {
            return \in_array($key, self::WEBHOOK_KEYS, true);
        }, \ARRAY_FILTER_USE_KEY);
    }
}
