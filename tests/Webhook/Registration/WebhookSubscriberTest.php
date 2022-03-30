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
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
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

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function setUp(): void
    {
        $this->systemConfigService = $this->getContainer()->get(SystemConfigService::class);
        $this->systemConfigService->set(Settings::CLIENT_ID, 'defaultClientId');
        $this->systemConfigService->set(Settings::CLIENT_SECRET, 'defaultClientSecret');
        $this->systemConfigService->set(Settings::SANDBOX, false);
    }

    public function tearDown(): void
    {
        $this->systemConfigService->delete(Settings::CLIENT_ID);
        $this->systemConfigService->delete(Settings::CLIENT_SECRET);
        $this->systemConfigService->delete(Settings::SANDBOX);
    }

    public function testRemoveWebhookWithInheritedConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => self::WEBHOOK_ID, Defaults::SALES_CHANNEL => null])
             ->removeSalesChannelWebhookConfiguration($this->createEvent());

        static::assertSame(self::WEBHOOK_ID, $this->systemConfigService->getString(Settings::WEBHOOK_ID, Defaults::SALES_CHANNEL));
    }

    public function testRemoveWebhookWithOwnConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => null, Defaults::SALES_CHANNEL => self::WEBHOOK_ID])
             ->removeSalesChannelWebhookConfiguration($this->createEvent());

        static::assertEmpty($this->systemConfigService->getString(Settings::WEBHOOK_ID, Defaults::SALES_CHANNEL));
    }

    public function testRemoveWebhookWithNoConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => null, Defaults::SALES_CHANNEL => null])
             ->removeSalesChannelWebhookConfiguration($this->createEvent());

        static::assertEmpty($this->systemConfigService->getString(Settings::WEBHOOK_ID, Defaults::SALES_CHANNEL));
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
        $webhookService = new WebhookService(
            new WebhookResource($this->createPayPalClientFactoryWithService($this->systemConfigService)),
            new WebhookRegistry(new DummyCollection([])),
            $this->systemConfigService,
            new RouterMock()
        );

        foreach ($configuration as $salesChannelId => $webhookId) {
            if ($salesChannelId === '') {
                $salesChannelId = null;
            }
            $this->systemConfigService->set(Settings::WEBHOOK_ID, $webhookId, $salesChannelId);
        }

        return new WebhookSubscriber(
            new NullLogger(),
            $this->systemConfigService,
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
