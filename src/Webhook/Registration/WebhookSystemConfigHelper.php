<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Psr\Log\LoggerInterface;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookSystemConfigHelper
{
    private const WEBHOOK_KEYS = [
        SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId',
        SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret',
        SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox',
        SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox',
        SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox',
    ];

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var WebhookServiceInterface
     */
    private $webhookService;

    public function __construct(
        LoggerInterface $logger,
        SettingsServiceInterface $settingsService,
        WebhookServiceInterface $webhookService
    ) {
        $this->logger = $logger;
        $this->settingsService = $settingsService;
        $this->webhookService = $webhookService;
    }

    public function configHasPayPalSettings(array $kvs): bool
    {
        return !empty(\array_intersect(\array_keys($kvs), self::WEBHOOK_KEYS));
    }

    public function checkWebhook(SwagPayPalSettingStruct $oldSettings, ?string $salesChannelId): array
    {
        $errors = [];
        $newSettings = $this->settingsService->getSettings($salesChannelId, false);

        if ($this->isWebhookChanged($oldSettings, $newSettings)) {
            try {
                $this->webhookService->deregisterWebhook($salesChannelId, $oldSettings);
            } catch (\Throwable $e) {
                $errors[] = $e;
                $this->logger->error($e->getMessage(), ['error' => $e]);
            }
        }

        try {
            $this->webhookService->registerWebhook($salesChannelId);
        } catch (\Throwable $e) {
            $errors[] = $e;
            $this->logger->error($e->getMessage(), ['error' => $e]);
        }

        return $errors;
    }

    private function isWebhookChanged(SwagPayPalSettingStruct $oldSettings, SwagPayPalSettingStruct $newSettings): bool
    {
        $oldSettingsFiltered = \array_filter(
            $oldSettings->jsonSerialize(),
            static function (string $key) {
                return \in_array(SettingsService::SYSTEM_CONFIG_DOMAIN . $key, self::WEBHOOK_KEYS, true);
            },
            \ARRAY_FILTER_USE_KEY
        );

        $newSettingsFiltered = \array_filter(
            $newSettings->jsonSerialize(),
            static function (string $key) {
                return \in_array(SettingsService::SYSTEM_CONFIG_DOMAIN . $key, self::WEBHOOK_KEYS, true);
            },
            \ARRAY_FILTER_USE_KEY
        );

        return !empty(\array_diff_assoc($oldSettingsFiltered, $newSettingsFiltered));
    }
}
