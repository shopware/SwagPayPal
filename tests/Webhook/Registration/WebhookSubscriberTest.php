<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Service\SettingsService;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DummyCollection;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Webhook\Registration\WebhookSubscriber;
use Swag\PayPal\Webhook\WebhookRegistry;
use Swag\PayPal\Webhook\WebhookService;

class WebhookSubscriberTest extends TestCase
{
    use DatabaseTransactionBehaviour;
    use ServicesTrait;

    private const WEBHOOK_ID = 'someWebhookId';

    public function setUp(): void
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get(SettingsService::class);
        $settingsService->updateSettings(['clientId' => 'defaultClientId', 'clientSecret' => 'defaultClientSecret', 'sandbox' => false]);
    }

    public function testRemoveWebhookWithInheritedConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => self::WEBHOOK_ID, Defaults::SALES_CHANNEL => null])
             ->removeSalesChannelWebhookConfiguration($this->createEvent());

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get(SettingsService::class);
        static::assertSame(self::WEBHOOK_ID, $settingsService->getSettings(Defaults::SALES_CHANNEL)->getWebhookId());
        static::assertNull($settingsService->getSettings(Defaults::SALES_CHANNEL, false)->getWebhookId());
    }

    public function testRemoveWebhookWithOwnConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => null, Defaults::SALES_CHANNEL => self::WEBHOOK_ID])
             ->removeSalesChannelWebhookConfiguration($this->createEvent());

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get(SettingsService::class);
        static::assertNull($settingsService->getSettings(Defaults::SALES_CHANNEL)->getWebhookId());
        static::assertNull($settingsService->getSettings(Defaults::SALES_CHANNEL, false)->getWebhookId());
    }

    public function testRemoveWebhookWithNoConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => null, Defaults::SALES_CHANNEL => null])
             ->removeSalesChannelWebhookConfiguration($this->createEvent());

        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get(SettingsService::class);
        static::assertNull($settingsService->getSettings(Defaults::SALES_CHANNEL)->getWebhookId());
        static::assertNull($settingsService->getSettings(Defaults::SALES_CHANNEL, false)->getWebhookId());
    }

    public function testSubscribedEvents(): void
    {
        static::assertEqualsCanonicalizing([
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'removeSalesChannelWebhookConfiguration',
        ], WebhookSubscriber::getSubscribedEvents());
    }

    /**
     * @param array<string, string|null> $configuration ([salesChannelId => webhookId])
     */
    private function createWebhookSubscriber(array $configuration): WebhookSubscriber
    {
        /** @var SettingsService $settingsService */
        $settingsService = $this->getContainer()->get(SettingsService::class);

        $webhookService = new WebhookService(
            new WebhookResource($this->createPayPalClientFactoryWithService($settingsService)),
            new WebhookRegistry(new DummyCollection([])),
            $settingsService,
            new RouterMock()
        );

        foreach ($configuration as $salesChannelId => $webhookId) {
            if ($salesChannelId === '') {
                $salesChannelId = null;
            }
            $settingsService->updateSettings([WebhookService::WEBHOOK_ID_KEY => $webhookId], $salesChannelId);
        }

        return new WebhookSubscriber(
            new NullLogger(),
            $settingsService,
            $webhookService
        );
    }

    private function createEvent(): EntityDeletedEvent
    {
        $writeResult = new EntityWriteResult(
            Defaults::SALES_CHANNEL,
            ['id' => Defaults::SALES_CHANNEL],
            SalesChannelDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_DELETE,
            new EntityExistence(
                SalesChannelDefinition::ENTITY_NAME,
                ['id' => Defaults::SALES_CHANNEL],
                true,
                false,
                false,
                ['exists' => '1']
            )
        );

        return new EntityDeletedEvent(
            SalesChannelDefinition::ENTITY_NAME,
            [$writeResult],
            Context::createDefaultContext()
        );
    }
}
