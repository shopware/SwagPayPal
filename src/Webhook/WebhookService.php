<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use Swag\PayPal\PayPal\Api\CreateWebhooks;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\PayPal\Resource\WebhookResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Webhook\Exception\WebhookAlreadyExistsException;
use Swag\PayPal\Webhook\Exception\WebhookIdInvalidException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookService implements WebhookServiceInterface
{
    public const WEBHOOK_TOKEN_CONFIG_KEY = 'webhookExecuteToken';

    public const WEBHOOK_CREATED = 'created';
    public const WEBHOOK_UPDATED = 'updated';
    public const NO_WEBHOOK_ACTION_REQUIRED = 'nothing';

    /**
     * @deprecated tag:v2.0.0 - Will be removed without replacement
     */
    public const PAYPAL_WEBHOOK_ROUTE = 'paypal.webhook.execute';
    public const PAYPAL_WEBHOOK_TOKEN_NAME = 'sw-token';
    public const PAYPAL_WEBHOOK_TOKEN_LENGTH = 32;

    /**
     * @var WebhookResource
     */
    private $webhookResource;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var WebhookRegistry
     */
    private $webhookRegistry;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    public function __construct(
        WebhookResource $webhookResource,
        WebhookRegistry $webhookRegistry,
        SettingsServiceInterface $settingsService,
        RouterInterface $router
    ) {
        $this->webhookResource = $webhookResource;
        $this->webhookRegistry = $webhookRegistry;
        $this->router = $router;
        $this->settingsService = $settingsService;
    }

    public function registerWebhook(?string $salesChannelId): string
    {
        $settings = $this->settingsService->getSettings($salesChannelId);

        $webhookExecuteToken = $settings->getWebhookExecuteToken();
        if ($webhookExecuteToken === null) {
            $webhookExecuteToken = Random::getAlphanumericString(self::PAYPAL_WEBHOOK_TOKEN_LENGTH);
        }

        $this->router->getContext()->setScheme('https');
        $webhookUrl = $this->router->generate(
            'api.action.paypal.webhook.execute',
            [self::PAYPAL_WEBHOOK_TOKEN_NAME => $webhookExecuteToken, 'version' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $webhookId = $settings->getWebhookId();

        if ($webhookId === null) {
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

    public function executeWebhook(Webhook $webhook, Context $context): void
    {
        $webhookHandler = $this->webhookRegistry->getWebhookHandler($webhook->getEventType());
        $webhookHandler->invoke($webhook, $context);
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

            $this->settingsService->updateSettings(
                [
                    self::WEBHOOK_TOKEN_CONFIG_KEY => $webhookExecuteToken,
                    'webhookId' => $webhookId,
                ],
                $salesChannelId
            );

            return self::WEBHOOK_CREATED;
        } catch (WebhookAlreadyExistsException $e) {
            return self::NO_WEBHOOK_ACTION_REQUIRED;
        }
    }
}
