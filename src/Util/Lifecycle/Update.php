<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Lifecycle;

use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Webhook\WebhookService;

class Update
{
    /**
     * @var SystemConfigService
     */
    private $systemConfig;

    /**
     * @var WebhookService|null
     */
    private $webhookService;

    public function __construct(SystemConfigService $systemConfig, ?WebhookService $webhookService)
    {
        $this->systemConfig = $systemConfig;
        $this->webhookService = $webhookService;
    }

    public function update(UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.1.0', '<')) {
            $this->updateTo110();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.3.0', '<')) {
            $this->updateTo130();
        }

        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.7.0', '<')) {
            $this->updateTo170();
        }
    }

    private function updateTo110(): void
    {
        $this->systemConfig->set(SettingsService::SYSTEM_CONFIG_DOMAIN . 'installmentBannerEnabled', true);
    }

    private function updateTo130(): void
    {
        if (!$this->systemConfig->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'sandbox')) {
            return;
        }

        $previousClientId = $this->systemConfig->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId');
        $previousClientSecret = $this->systemConfig->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret');
        $previousClientIdSandbox = $this->systemConfig->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox');
        $previousClientSecretSandbox = $this->systemConfig->get(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox');

        if ($previousClientId && $previousClientSecret
            && $previousClientIdSandbox === null && $previousClientSecretSandbox === null) {
            $this->systemConfig->set(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientIdSandbox', $previousClientId);
            $this->systemConfig->set(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecretSandbox', $previousClientSecret);
            $this->systemConfig->set(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientId', '');
            $this->systemConfig->set(SettingsService::SYSTEM_CONFIG_DOMAIN . 'clientSecret', '');
        }
    }

    private function updateTo170(): void
    {
        if ($this->webhookService === null) {
            // If the WebhookService is `null`, the plugin is deactivated.
            return;
        }

        $this->webhookService->registerWebhook(null);
    }
}
