<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Registration;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelEvents;
use Shopware\Core\System\SystemConfig\Event\BeforeSystemConfigMultipleChangedEvent;
use Shopware\Core\System\SystemConfig\Event\SystemConfigMultipleChangedEvent;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Webhook\Registration\WebhookSubscriber;
use Swag\PayPal\Webhook\Registration\WebhookSystemConfigHelper;
use Swag\PayPal\Webhook\WebhookRegistry;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookSubscriberTest extends TestCase
{
    use ServicesTrait;

    private const WEBHOOK_ID = 'someWebhookId';

    private SystemConfigService $systemConfigService;

    private WebhookSystemConfigHelper&MockObject $webhookSystemConfigHelper;

    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->systemConfigService = SystemConfigServiceMock::createWithCredentials();
        $this->webhookSystemConfigHelper = $this->createMock(WebhookSystemConfigHelper::class);
        $this->requestStack = new RequestStack();
    }

    public function testRemoveWebhookWithInheritedConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => self::WEBHOOK_ID, TestDefaults::SALES_CHANNEL => null])
            ->removeSalesChannelWebhookConfiguration($this->createEvent());

        static::assertSame(self::WEBHOOK_ID, $this->systemConfigService->getString(Settings::WEBHOOK_ID, TestDefaults::SALES_CHANNEL));
    }

    public function testRemoveWebhookWithOwnConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => self::WEBHOOK_ID])
            ->removeSalesChannelWebhookConfiguration($this->createEvent());

        static::assertEmpty($this->systemConfigService->getString(Settings::WEBHOOK_ID, TestDefaults::SALES_CHANNEL));
    }

    public function testRemoveWebhookWithNoConfiguration(): void
    {
        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->removeSalesChannelWebhookConfiguration($this->createEvent());

        static::assertEmpty($this->systemConfigService->getString(Settings::WEBHOOK_ID, TestDefaults::SALES_CHANNEL));
    }

    public function testCheckWebhookBefore(): void
    {
        $event = new BeforeSystemConfigMultipleChangedEvent(['some-key' => 'some-value'], null);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('checkWebhookBefore')
            ->with(['' => ['some-key' => 'some-value']]);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('needsCheck')
            ->with(['some-key' => 'some-value'])
            ->willReturn(true);

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookBefore($event);
    }

    public function testCheckWebhookBeforeWithSalesChannelId(): void
    {
        $event = new BeforeSystemConfigMultipleChangedEvent(['some-key' => 'some-value'], 'some-sales-channel-id');

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('checkWebhookBefore')
            ->with(['some-sales-channel-id' => ['some-key' => 'some-value']]);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('needsCheck')
            ->with(['some-key' => 'some-value'])
            ->willReturn(true);

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookBefore($event);
    }

    public function testCheckWebhookBeforeWithSaveRoute(): void
    {
        $request = new Request(attributes: ['_route' => 'api.action.paypal.settings.save']);
        $this->requestStack->push($request);

        $event = new BeforeSystemConfigMultipleChangedEvent(['some-key' => 'some-value'], 'some-sales-channel-id');

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('checkWebhookBefore');

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('needsCheck');

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookBefore($event);
    }

    public function testCheckWebhookBeforeWithNoCheckNeeded(): void
    {
        $event = new BeforeSystemConfigMultipleChangedEvent(['some-key' => 'some-value'], 'some-sales-channel-id');

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('checkWebhookBefore');

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('needsCheck')
            ->with(['some-key' => 'some-value'])
            ->willReturn(false);

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookBefore($event);
    }

    public function testCheckWebhookAfter(): void
    {
        $event = new SystemConfigMultipleChangedEvent(['some-key' => 'some-value'], null);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('checkWebhookAfter')
            ->with(['']);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('needsCheck')
            ->with(['some-key' => 'some-value'])
            ->willReturn(true);

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookAfter($event);
    }

    public function testCheckWebhookAfterWithSalesChannelId(): void
    {
        $event = new SystemConfigMultipleChangedEvent(['some-key' => 'some-value'], 'some-sales-channel-id');

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('checkWebhookAfter')
            ->with(['some-sales-channel-id']);

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('needsCheck')
            ->with(['some-key' => 'some-value'])
            ->willReturn(true);

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookAfter($event);
    }

    public function testCheckWebhookAfterWithSaveRoute(): void
    {
        $request = new Request(attributes: ['_route' => 'api.action.paypal.settings.save']);
        $this->requestStack->push($request);

        $event = new SystemConfigMultipleChangedEvent(['some-key' => 'some-value'], 'some-sales-channel-id');

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('checkWebhookAfter');

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('needsCheck');

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookAfter($event);
    }

    public function testCheckWebhookAfterWithNoCheckNeeded(): void
    {
        $event = new SystemConfigMultipleChangedEvent(['some-key' => 'some-value'], 'some-sales-channel-id');

        $this->webhookSystemConfigHelper
            ->expects(static::never())
            ->method('checkWebhookAfter');

        $this->webhookSystemConfigHelper
            ->expects(static::once())
            ->method('needsCheck')
            ->with(['some-key' => 'some-value'])
            ->willReturn(false);

        $this->createWebhookSubscriber(['' => null, TestDefaults::SALES_CHANNEL => null])
            ->checkWebhookAfter($event);
    }

    public function testSubscribedEvents(): void
    {
        static::assertEqualsCanonicalizing([
            SalesChannelEvents::SALES_CHANNEL_DELETED => 'removeSalesChannelWebhookConfiguration',
            BeforeSystemConfigMultipleChangedEvent::class => 'checkWebhookBefore',
            SystemConfigMultipleChangedEvent::class => 'checkWebhookAfter',
        ], WebhookSubscriber::getSubscribedEvents());
    }

    /**
     * @param array<string, string|null> $configuration ([salesChannelId => webhookId])
     */
    private function createWebhookSubscriber(array $configuration): WebhookSubscriber
    {
        $webhookService = new WebhookService(
            new WebhookResource(new PayPalClientFactoryMock(new NullLogger())),
            new WebhookRegistry([]),
            $this->systemConfigService,
            $this->createMock(RouterInterface::class),
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
            $webhookService,
            $this->webhookSystemConfigHelper,
            $this->requestStack,
        );
    }

    private function createEvent(): EntityDeletedEvent
    {
        $writeResult = new EntityWriteResult(
            TestDefaults::SALES_CHANNEL,
            ['id' => TestDefaults::SALES_CHANNEL],
            SalesChannelDefinition::ENTITY_NAME,
            EntityWriteResult::OPERATION_DELETE,
            new EntityExistence(
                SalesChannelDefinition::ENTITY_NAME,
                ['id' => TestDefaults::SALES_CHANNEL],
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
