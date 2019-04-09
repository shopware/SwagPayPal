<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Webhook;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Util\Random;
use SwagPayPal\PayPal\Api\CreateWebhooks;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\PayPal\Resource\WebhookResource;
use SwagPayPal\Setting\Exception\PayPalSettingsNotFoundException;
use SwagPayPal\Setting\Service\SettingsServiceInterface;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookService implements WebhookServiceInterface
{
    public const WEBHOOK_CREATED = 'created';
    public const WEBHOOK_UPDATED = 'updated';
    public const NO_WEBHOOK_ACTION_REQUIRED = 'nothing';

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
        $this->settingsService = $settingsService;
        $this->router = $router;
    }

    /**
     * @throws PayPalSettingsNotFoundException
     */
    public function registerWebhook(Context $context): string
    {
        $settings = $this->settingsService->getSettings($context);

        $webhookExecuteToken = $settings->getWebhookExecuteToken();
        if ($webhookExecuteToken === null) {
            $webhookExecuteToken = Random::getAlphanumericString(self::PAYPAL_WEBHOOK_TOKEN_LENGTH);
        }

        $webhookUrl = $this->router->generate(
            self::PAYPAL_WEBHOOK_ROUTE,
            [self::PAYPAL_WEBHOOK_TOKEN_NAME => $webhookExecuteToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $webhookId = $settings->getWebhookId();
        $settingsUuid = $settings->getId();

        if ($webhookId === null) {
            return $this->createWebhook($context, $webhookUrl, $settingsUuid, $webhookExecuteToken);
        }

        try {
            $registeredWebhookUrl = $this->webhookResource->getWebhookUrl($webhookId, $context);
            if ($registeredWebhookUrl === $webhookUrl) {
                return self::NO_WEBHOOK_ACTION_REQUIRED;
            }
        } catch (WebhookIdInvalidException $e) {
            // do nothing, so the following code will be executed
        }

        try {
            $this->webhookResource->updateWebhook($webhookUrl, $webhookId, $context);

            return self::WEBHOOK_UPDATED;
        } catch (WebhookIdInvalidException $e) {
            return $this->createWebhook($context, $webhookUrl, $settingsUuid, $webhookExecuteToken);
        }
    }

    /**
     * @throws WebhookException
     */
    public function executeWebhook(Webhook $webhook, Context $context): void
    {
        $webhookHandler = $this->webhookRegistry->getWebhookHandler($webhook->getEventType());
        $webhookHandler->invoke($webhook, $context);
    }

    private function createWebhook(
        Context $context,
        string $webhookUrl,
        string $settingsUuid,
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
                $context
            );

            $this->settingsService->updateSettings([
                'id' => $settingsUuid,
                'webhookId' => $webhookId,
                'webhookExecuteToken' => $webhookExecuteToken,
            ], $context);

            return self::WEBHOOK_CREATED;
        } catch (WebhookAlreadyExistsException $e) {
            return self::NO_WEBHOOK_ACTION_REQUIRED;
        }
    }
}
