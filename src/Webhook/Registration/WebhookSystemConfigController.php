<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\Api\SystemConfigController;
use Shopware\Core\System\SystemConfig\Service\ConfigurationService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Validation\SystemConfigValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Package('checkout')]
#[Route(defaults: ['_routeScope' => ['api']])]
class WebhookSystemConfigController extends SystemConfigController
{
    public const WEBHOOK_ERRORS_KEY = 'payPalWebhookErrors';

    private WebhookSystemConfigHelper $webhookSystemConfigHelper;

    /**
     * @internal
     */
    public function __construct(
        ConfigurationService $configurationService,
        SystemConfigService $systemConfig,
        WebhookSystemConfigHelper $webhookSystemConfigHelper,
        SystemConfigValidator $systemConfigValidator,
    ) {
        parent::__construct($configurationService, $systemConfig, $systemConfigValidator);
        $this->webhookSystemConfigHelper = $webhookSystemConfigHelper;
    }

    public function saveConfiguration(Request $request): JsonResponse
    {
        $salesChannelId = $request->query->get('salesChannelId');
        if (!\is_string($salesChannelId) || $salesChannelId === '') {
            $salesChannelId = 'null';
        }
        $data = [$salesChannelId => $request->request->all()];

        $errors = $this->webhookSystemConfigHelper->checkWebhookBefore($data);

        $response = parent::saveConfiguration($request);

        $errors = \array_merge($errors, $this->webhookSystemConfigHelper->checkWebhookAfter(\array_keys($data)));

        if (empty($errors)) {
            return $response;
        }

        return new JsonResponse([self::WEBHOOK_ERRORS_KEY => \array_map(static function (\Throwable $e) {
            return $e->getMessage();
        }, $errors)]);
    }

    public function batchSaveConfiguration(Request $request, Context $context): JsonResponse
    {
        /** @var array<string, array<string, mixed>> $data */
        $data = $request->request->all();
        $errors = $this->webhookSystemConfigHelper->checkWebhookBefore($data);

        $response = parent::batchSaveConfiguration($request, $context);

        $errors = \array_merge($errors, $this->webhookSystemConfigHelper->checkWebhookAfter(\array_keys($data)));

        if (empty($errors)) {
            return $response;
        }

        return new JsonResponse([self::WEBHOOK_ERRORS_KEY => \array_map(static function (\Throwable $e) {
            return $e->getMessage();
        }, $errors)]);
    }
}
