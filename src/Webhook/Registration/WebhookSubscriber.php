<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Webhook\WebhookDeregistrationServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var SettingsServiceInterface
     */
    private $settingsService;

    /**
     * @deprecated tag:v2.0.0 - Will be switched to WebhookServiceInterface
     *
     * @var WebhookDeregistrationServiceInterface
     */
    private $webhookService;

    /**
     * @deprecated tag:v2.0.0 - $webhookService will be of type WebhookServiceInterface
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsServiceInterface $settingsService,
        WebhookDeregistrationServiceInterface $webhookService
    ) {
        $this->logger = $logger;
        $this->settingsService = $settingsService;
        $this->webhookService = $webhookService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'removeSalesChannelWebhookConfiguration',
        ];
    }

    public function removeSalesChannelWebhookConfiguration(EntityDeletedEvent $event): void
    {
        if (!$this->deleteableWebhook()) {
            return;
        }

        foreach ($event->getIds() as $id) {
            $salesChannelId = $id['id'];

            try {
                $settings = $this->settingsService->getSettings($salesChannelId, false);
                $this->webhookService->deregisterWebhook($salesChannelId, $settings);
            } catch (\Throwable $e) {
                $this->logger->error('[PayPal Webhook Deregistration] ' . $e->getMessage(), ['error' => $e]);
            }
        }
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
