<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 * ToDo PPI-65 - add Acl, when min-version >= 6.3
 */
class WebhookSystemConfigController
{
    public const WEBHOOK_ERRORS_KEY = 'payPalWebhookErrors';

    /**
     * @var SystemConfigController
     */
    private $inner;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @var WebhookSystemConfigHelper
     */
    private $webhookSystemConfigHelper;

    public function __construct(
        SystemConfigController $inner,
        SettingsServiceInterface $settingsService,
        WebhookSystemConfigHelper $webhookSystemConfigHelper
    ) {
        $this->inner = $inner;
        $this->settingsService = $settingsService;
        $this->webhookSystemConfigHelper = $webhookSystemConfigHelper;
    }

    /**
     * @Route("/api/v{version}/_action/system-config/check", name="api.action.core.system-config.check", methods={"GET"})
     */
    public function checkConfiguration(Request $request): JsonResponse
    {
        return $this->inner->checkConfiguration($request);
    }

    /**
     * @Route("/api/v{version}/_action/system-config/schema", name="api.action.core.system-config", methods={"GET"})
     *
     * @throws MissingRequestParameterException
     */
    public function getConfiguration(Request $request): JsonResponse
    {
        return $this->inner->getConfiguration($request);
    }

    /**
     * @Route("/api/v{version}/_action/system-config", name="api.action.core.system-config.value", methods={"GET"})
     */
    public function getConfigurationValues(Request $request): JsonResponse
    {
        return $this->inner->getConfigurationValues($request);
    }

    /**
     * @Route("/api/v{version}/_action/system-config", name="api.action.core.save.system-config", methods={"POST"})
     */
    public function saveConfiguration(Request $request): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');

        $oldSettings = $this->settingsService->getSettings($salesChannelId, false);
        $kvs = $request->request->all();

        $response = $this->inner->saveConfiguration($request);

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

    /**
     * @Route("/api/v{version}/_action/system-config/batch", name="api.action.core.save.system-config.batch", methods={"POST"})
     */
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

        $response = $this->inner->batchSaveConfiguration($request);

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
