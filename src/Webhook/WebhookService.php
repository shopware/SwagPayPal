<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\CreateWebhooks;
use Swag\PayPal\RestApi\V1\Api\Webhook as WebhookV1;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\RestApi\V2\Api\Webhook as WebhookV2;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookService implements WebhookServiceInterface
{
    public const WEBHOOK_CREATED = 'created';
    public const WEBHOOK_UPDATED = 'updated';
    public const WEBHOOK_DELETED = 'deleted';
    public const NO_WEBHOOK_ACTION_REQUIRED = 'nothing';

    public const PAYPAL_WEBHOOK_TOKEN_NAME = 'sw-token';
    public const PAYPAL_WEBHOOK_TOKEN_LENGTH = 32;

    private WebhookResource $webhookResource;

    private RouterInterface $router;

    private WebhookRegistry $webhookRegistry;

    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(
        WebhookResource $webhookResource,
        WebhookRegistry $webhookRegistry,
        SystemConfigService $systemConfigService,
        RouterInterface $router
    ) {
        $this->webhookResource = $webhookResource;
        $this->webhookRegistry = $webhookRegistry;
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    public function registerWebhook(?string $salesChannelId): string
    {
        $webhookExecuteToken = $this->systemConfigService->getString(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);

        if ($salesChannelId !== null && $webhookExecuteToken === $this->systemConfigService->getString(Settings::WEBHOOK_EXECUTE_TOKEN)) {
            // inherited
            $webhookExecuteToken = '';
        }

        if ($webhookExecuteToken === '') {
            $webhookExecuteToken = Random::getAlphanumericString(self::PAYPAL_WEBHOOK_TOKEN_LENGTH);
        }

        $this->router->getContext()->setScheme('https');
        $webhookUrl = $this->router->generate(
            'api.action.paypal.webhook.execute',
            [self::PAYPAL_WEBHOOK_TOKEN_NAME => $webhookExecuteToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $webhookId = $this->systemConfigService->getString(Settings::WEBHOOK_ID, $salesChannelId);

        if ($salesChannelId !== null && $webhookId === $this->systemConfigService->getString(Settings::WEBHOOK_ID)) {
            return $this->createWebhook($salesChannelId, $webhookUrl, $webhookExecuteToken);
        }

        if ($webhookId === '') {
            return $this->createWebhook($salesChannelId, $webhookUrl, $webhookExecuteToken);
        }

        try {
            $registeredWebhookUrl = $this->webhookResource->getWebhookUrl($webhookId, $salesChannelId);
            if ($registeredWebhookUrl === $webhookUrl) {
                return self::NO_WEBHOOK_ACTION_REQUIRED;
            }
        } catch (WebhookIdInvalidException $e) {
            // do nothing, so the following code will be executed
        }

        try {
            $this->webhookResource->updateWebhook($webhookUrl, $webhookId, $salesChannelId);

            return self::WEBHOOK_UPDATED;
        } catch (WebhookIdInvalidException $e) {
            return $this->createWebhook($salesChannelId, $webhookUrl, $webhookExecuteToken);
        }
    }

    /**
     * @param WebhookV1|WebhookV2 $webhook
     */
    public function executeWebhook(PayPalApiStruct $webhook, Context $context): void
    {
        $webhookHandler = $this->webhookRegistry->getWebhookHandler($webhook->getEventType());
        $webhookHandler->invoke($webhook, $context);
    }

    public function deregisterWebhook(?string $salesChannelId): string
    {
        $webhookId = $this->systemConfigService->getString(Settings::WEBHOOK_ID, $salesChannelId);

        if ($webhookId === '') {
            return WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
        }

        if ($salesChannelId !== null && $webhookId === $this->systemConfigService->getString(Settings::WEBHOOK_ID)) {
            // inherited
            return WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
        }

        try {
            $this->webhookResource->deleteWebhook($webhookId, $salesChannelId);
            $deleted = true;
        } catch (WebhookIdInvalidException $e) {
            $deleted = false;
        }

        $this->systemConfigService->delete(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);
        $this->systemConfigService->delete(Settings::WEBHOOK_ID, $salesChannelId);

        return $deleted ? WebhookService::WEBHOOK_DELETED : WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
    }

    private function createWebhook(
        ?string $salesChannelId,
        string $webhookUrl,
        string $webhookExecuteToken
    ): string {
        $requestData = [
            'url' => $webhookUrl,
            'event_types' => [['name' => WebhookEventTypes::ALL_EVENTS]],
        ];

        $createWebhooks = new CreateWebhooks();
        $createWebhooks->assign($requestData);

        try {
            $webhookId = $this->webhookResource->createWebhook(
                $webhookUrl,
                $createWebhooks,
                $salesChannelId
            );

            $this->systemConfigService->set(Settings::WEBHOOK_EXECUTE_TOKEN, $webhookExecuteToken, $salesChannelId);
            $this->systemConfigService->set(Settings::WEBHOOK_ID, $webhookId, $salesChannelId);

            return self::WEBHOOK_CREATED;
        } catch (WebhookAlreadyExistsException $e) {
            return self::NO_WEBHOOK_ACTION_REQUIRED;
        }
    }
}
