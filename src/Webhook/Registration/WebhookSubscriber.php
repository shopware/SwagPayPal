<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Webhook\Registration;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Webhook\WebhookServiceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    private SystemConfigService $systemConfigService;

    private WebhookServiceInterface $webhookService;

    public function __construct(
        LoggerInterface $logger,
        SystemConfigService $systemConfigService,
        WebhookServiceInterface $webhookService,
    ) {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;
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
        $generalWebhookId = $this->systemConfigService->getString(Settings::WEBHOOK_ID);
        foreach ($event->getIds() as $salesChannelId) {
            $webhookId = $this->systemConfigService->getString(Settings::WEBHOOK_ID, $salesChannelId);

            try {
                if ($webhookId !== $generalWebhookId) {
                    $this->webhookService->deregisterWebhook($salesChannelId);
                }
            } catch (\Throwable $e) {
                $this->logger->error($e->getMessage(), ['error' => $e]);
            }
        }
    }
}
