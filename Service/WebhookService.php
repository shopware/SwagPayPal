<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\RepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Util\Random;
use SwagPayPal\PayPal\Resource\WebhookResource;
use SwagPayPal\PayPal\Struct\Webhook;
use SwagPayPal\Setting\SwagPayPalSettingGeneralCollection;
use SwagPayPal\Webhook\Exception\WebhookAlreadyExistsException;
use SwagPayPal\Webhook\Exception\WebhookException;
use SwagPayPal\Webhook\Exception\WebhookIdInvalidException;
use SwagPayPal\Webhook\WebhookEventTypes;
use SwagPayPal\Webhook\WebhookRegistry;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class WebhookService
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
     * @var RepositoryInterface
     */
    private $settingGeneralRepo;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var WebhookRegistry
     */
    private $webhookRegistry;

    public function __construct(
        WebhookResource $webhookResource,
        RepositoryInterface $settingGeneralRepo,
        RouterInterface $router,
        WebhookRegistry $webhookRegistry
    ) {
        $this->webhookResource = $webhookResource;
        $this->settingGeneralRepo = $settingGeneralRepo;
        $this->router = $router;
        $this->webhookRegistry = $webhookRegistry;
    }

    public function registerWebhook(Context $context): string
    {
        /** @var SwagPayPalSettingGeneralCollection $settingsCollection */
        $settingsCollection = $this->settingGeneralRepo->search(new Criteria(), $context)->getEntities();
        $settings = $settingsCollection->first();

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
        try {
            $webhookId = $this->webhookResource->createWebhook(
                $webhookUrl,
                [WebhookEventTypes::ALL_EVENTS],
                $context
            );
            $this->updateSettings($settingsUuid, $webhookId, $webhookExecuteToken, $context);

            return self::WEBHOOK_CREATED;
        } catch (WebhookAlreadyExistsException $e) {
            return self::NO_WEBHOOK_ACTION_REQUIRED;
        }
    }

    private function updateSettings(
        string $settingsUuid,
        string $webhookId,
        string $webhookExecuteToken,
        Context $context
    ): void {
        $data = [
            'id' => $settingsUuid,
            'webhookId' => $webhookId,
            'webhookExecuteToken' => $webhookExecuteToken,
        ];
        $this->settingGeneralRepo->update([$data], $context);
    }
}
