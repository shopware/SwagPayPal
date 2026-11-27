<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Util\Random;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\PayPalSDK\Struct\V1\Webhook;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationChecker;
use Swag\PayPal\Webhook\Registration\WebhookRegistrationStatus;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;

#[Package('checkout')]
class WebhookService implements WebhookServiceInterface
{
    public const WEBHOOK_CREATED = 'created';
    public const WEBHOOK_UPDATED = 'updated';
    public const WEBHOOK_DELETED = 'deleted';
    public const NO_WEBHOOK_ACTION_REQUIRED = 'nothing';

    public const PAYPAL_WEBHOOK_TOKEN_NAME = 'sw-token';
    public const PAYPAL_WEBHOOK_TOKEN_LENGTH = 32;

    public const STATUS_WEBHOOK_MISSING = WebhookRegistrationStatus::Missing->value;
    public const STATUS_WEBHOOK_INVALID = WebhookRegistrationStatus::Invalid->value;
    public const STATUS_WEBHOOK_VALID = WebhookRegistrationStatus::Valid->value;
    public const STATUS_WEBHOOK_DISABLED = WebhookRegistrationStatus::Disabled->value;

    private WebhookResource $webhookResource;

    private WebhookRegistry $webhookRegistry;

    private SystemConfigService $systemConfigService;

    /**
     * @internal
     */
    public function __construct(
        WebhookResource $webhookResource,
        WebhookRegistry $webhookRegistry,
        SystemConfigService $systemConfigService,
        private readonly WebhookRegistrationChecker $registrationChecker,
        private readonly WebhookUrlBuilder $urlBuilder,
        private readonly LoggerInterface $logger,
    ) {
        $this->webhookResource = $webhookResource;
        $this->webhookRegistry = $webhookRegistry;
        $this->systemConfigService = $systemConfigService;
    }

    public function getStatus(?string $salesChannelId): string
    {
        return $this->registrationChecker->check($salesChannelId)->status->value;
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

        $webhookUrl = $this->createWebhookUrl($webhookExecuteToken);
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
        } catch (WebhookIdInvalidException) {
            // do nothing, so the following code will be executed
        }

        try {
            $this->webhookResource->updateWebhook($webhookUrl, $webhookId, $salesChannelId);

            $this->logger->info('Updated the URL of PayPal webhook {webhookId}', Log::context(
                code: LogCode::WEBHOOK_UPDATED,
                salesChannelId: $salesChannelId,
                webhookId: $webhookId,
            ));

            return self::WEBHOOK_UPDATED;
        } catch (WebhookIdInvalidException) {
            return $this->createWebhook($salesChannelId, $webhookUrl, $webhookExecuteToken);
        }
    }

    public function executeWebhook(Event $webhook, Context $context): void
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

            $this->logger->info('Removed PayPal webhook {webhookId}', Log::context(
                code: LogCode::WEBHOOK_DEREGISTERED,
                salesChannelId: $salesChannelId,
                webhookId: $webhookId,
            ));
        } catch (WebhookIdInvalidException) {
            $deleted = false;
        }

        $this->systemConfigService->delete(Settings::WEBHOOK_EXECUTE_TOKEN, $salesChannelId);
        $this->systemConfigService->delete(Settings::WEBHOOK_ID, $salesChannelId);

        return $deleted ? WebhookService::WEBHOOK_DELETED : WebhookService::NO_WEBHOOK_ACTION_REQUIRED;
    }

    private function createWebhook(
        ?string $salesChannelId,
        string $webhookUrl,
        string $webhookExecuteToken,
    ): string {
        $requestData = [
            'url' => $webhookUrl,
            'event_types' => [['name' => WebhookEventTypes::ALL_EVENTS]],
        ];

        $createWebhooks = new Webhook();
        $createWebhooks->assign($requestData);

        try {
            $webhookId = $this->webhookResource->createWebhook(
                $webhookUrl,
                $createWebhooks,
                $salesChannelId
            );

            $this->systemConfigService->set(Settings::WEBHOOK_EXECUTE_TOKEN, $webhookExecuteToken, $salesChannelId);
            $this->systemConfigService->set(Settings::WEBHOOK_ID, $webhookId, $salesChannelId);

            $this->logger->info('Registered PayPal webhook {webhookId}', Log::context(
                code: LogCode::WEBHOOK_REGISTERED,
                salesChannelId: $salesChannelId,
                webhookId: $webhookId,
            ));

            return self::WEBHOOK_CREATED;
        } catch (WebhookAlreadyExistsException) {
            $webhooks = $this->webhookResource->getAllWebhooks($salesChannelId);

            foreach ($webhooks as $webhook) {
                if ($webhook->getUrl() === $webhookUrl) {
                    $this->systemConfigService->set(Settings::WEBHOOK_ID, $webhook->getId(), $salesChannelId);

                    break;
                }
            }

            return self::NO_WEBHOOK_ACTION_REQUIRED;
        }
    }

    private function createWebhookUrl(string $executeToken): string
    {
        return $this->urlBuilder->build($executeToken);
    }
}
