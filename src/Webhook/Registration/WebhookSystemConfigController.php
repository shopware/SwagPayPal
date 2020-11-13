<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Shopware\Core\System\SystemConfig\Service\ConfigurationService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteScope(scopes={"api"})
 */
class WebhookSystemConfigController extends SystemConfigController
{
    public const WEBHOOK_ERRORS_KEY = 'payPalWebhookErrors';

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var WebhookSystemConfigHelper
     */
    private $webhookSystemConfigHelper;

    public function __construct(
        ConfigurationService $configurationService,
        SystemConfigService $systemConfig,
        SettingsServiceInterface $settingsService,
        WebhookSystemConfigHelper $webhookSystemConfigHelper
    ) {
        parent::__construct($configurationService, $systemConfig);
        $this->settingsService = $settingsService;
        $this->webhookSystemConfigHelper = $webhookSystemConfigHelper;
    }

    public function saveConfiguration(Request $request): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');

        $oldSettings = $this->settingsService->getSettings($salesChannelId, false);
        $kvs = $request->request->all();

        $response = parent::saveConfiguration($request);

        if (!$this->webhookSystemConfigHelper->configHasPayPalSettings($kvs)) {
            return $response;
        }

        $errors = $this->webhookSystemConfigHelper->checkWebhook($oldSettings, $salesChannelId);

        if (empty($errors)) {
            return $response;
        }

        return new JsonResponse([self::WEBHOOK_ERRORS_KEY => \array_map(static function (\Throwable $e) {
            return $e->getMessage();
        }, $errors)]);
    }

    public function batchSaveConfiguration(Request $request): JsonResponse
    {
        $oldSettings = [];

        if ($this->deleteableWebhook()) {
            foreach ($request->request->all() as $salesChannelId => $kvs) {
                if (!$this->webhookSystemConfigHelper->configHasPayPalSettings($kvs)) {
                    continue;
                }

                if ($salesChannelId === 'null' || !\is_string($salesChannelId)) {
                    $salesChannelId = null;
                }

                $oldSettings[$salesChannelId ?? ''] = $this->settingsService->getSettings($salesChannelId, false);
            }
        }

        $response = parent::batchSaveConfiguration($request);

        if (empty($oldSettings)) {
            return $response;
        }

        $errors = [];
        foreach ($oldSettings as $salesChannelId => $oldSetting) {
            $salesChannelId = $salesChannelId !== '' ? $salesChannelId : null;
            $newErrors = $this->webhookSystemConfigHelper->checkWebhook($oldSetting, $salesChannelId);

            if ($newErrors) {
                \array_push($errors, ...$newErrors);
            }
        }

        if (empty($errors)) {
            return $response;
        }

        return new JsonResponse([self::WEBHOOK_ERRORS_KEY => \array_map(static function (\Throwable $e) {
            return $e->getMessage();
        }, $errors)]);
    }

    /**
     * @deprecated tag:v2.0.0 - will be removed
     */
    private function deleteableWebhook(): bool
    {
        $reflection = new \ReflectionMethod($this->settingsService, 'getSettings');

        return $reflection->getNumberOfParameters() >= 2;
    }
}
