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
use Swag\PayPal\Webhook\WebhookServiceInterface;
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
     * @var WebhookServiceInterface
     */
    private $webhookService;

    public function __construct(
        LoggerInterface $logger,
        SettingsServiceInterface $settingsService,
        WebhookServiceInterface $webhookService
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
        foreach ($event->getIds() as $id) {
            try {
                $settings = $this->settingsService->getSettings($id, false);
                $this->webhookService->deregisterWebhook($id, $settings);
            } catch (\Throwable $e) {
                $this->logger->error('[PayPal Webhook Deregistration] ' . $e->getMessage(), ['error' => $e]);
            }
        }
    }
}
