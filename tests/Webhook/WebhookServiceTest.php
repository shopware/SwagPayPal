<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Setting\Service\SystemConfigServiceMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\RestApi\V1\Resource\WebhookResourceTest;
use Swag\PayPal\Webhook\WebhookRegistry;
use Swag\PayPal\Webhook\WebhookService;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
class WebhookServiceTest extends TestCase
{
    use ServicesTrait;

    public const THROW_WEBHOOK_ID_INVALID = 'webhookIdInvalid';

    public const THROW_WEBHOOK_ALREADY_EXISTS = 'webhookAlreadyExists';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';
    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    private OrderTransactionRepoMock $orderTransactionRepo;

    private SystemConfigServiceMock $systemConfig;

    private RouterInterface&MockObject $router;

    private WebhookService $webhookService;

    protected function setUp(): void
    {
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
        $this->systemConfig = SystemConfigServiceMock::createWithCredentials();
        $this->router = $this->createMock(RouterInterface::class);

        $this->webhookService = new WebhookService(
            $this->createWebhookResource($this->systemConfig),
            new WebhookRegistry([new DummyWebhook($this->orderTransactionRepo)]),
            $this->systemConfig,
            $this->router,
        );
    }

    public function testStatusWebhookWithoutId(): void
    {
        $result = $this->webhookService->getStatus(null);

        static::assertSame(WebhookService::STATUS_WEBHOOK_MISSING, $result);
    }

    public function testStatusWebhookWithoutRegisteredWebhook(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID);
        $result = $this->webhookService->getStatus(null);

        static::assertSame(WebhookService::STATUS_WEBHOOK_MISSING, $result);
    }

    public function testStatusWebhookRegisteredWebhookEqualsPayPals(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, 'someId');
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, 'someToken');

        $this->router
            ->expects(static::once())
            ->method('generate')
            ->with('api.action.paypal.webhook.execute', [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'someToken'], RouterInterface::ABSOLUTE_URL)
            ->willReturn(GuzzleClientMock::GET_WEBHOOK_URL);

        $result = $this->webhookService->getStatus(null);

        static::assertSame(WebhookService::STATUS_WEBHOOK_VALID, $result);
    }

    public function testStatusWebhookRegisteredWebhookNotEqualsPayPals(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, 'someId');
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, 'someToken');

        $this->router
            ->expects(static::once())
            ->method('generate')
            ->with('api.action.paypal.webhook.execute', [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'someToken'], RouterInterface::ABSOLUTE_URL)
            ->willReturn(GuzzleClientMock::GET_WEBHOOK_URL . 'Invalid');

        $result = $this->webhookService->getStatus(null);

        static::assertSame(WebhookService::STATUS_WEBHOOK_INVALID, $result);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndId(): void
    {
        $this->router
            ->expects(static::once())
            ->method('generate')
            ->with('api.action.paypal.webhook.execute', [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => 'someToken'], RouterInterface::ABSOLUTE_URL)
            ->willReturn(GuzzleClientMock::GET_WEBHOOK_URL);

        $this->systemConfig->set(Settings::WEBHOOK_ID, 'someId');
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, 'someToken');
        $result = $this->webhookService->registerWebhook(null);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, self::ALREADY_EXISTING_WEBHOOK_ID);

        $result = $this->webhookService->registerWebhook(null);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $result = $this->webhookService->registerWebhook(null);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $this->systemConfig->getString(Settings::WEBHOOK_ID));
        static::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($this->systemConfig->getString(Settings::WEBHOOK_EXECUTE_TOKEN)));
    }

    public function testDeregisterWebhookWithExistingInheritedId(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, self::ALREADY_EXISTING_WEBHOOK_ID);
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $result = $this->webhookService->deregisterWebhook(TestDefaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
        static::assertSame(self::ALREADY_EXISTING_WEBHOOK_ID, $this->systemConfig->get(Settings::WEBHOOK_ID));
        static::assertSame(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN, $this->systemConfig->get(Settings::WEBHOOK_EXECUTE_TOKEN));
    }

    public function testDeregisterWebhookWithExistingId(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, self::ALREADY_EXISTING_WEBHOOK_ID);
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $result = $this->webhookService->deregisterWebhook(null);

        static::assertSame(WebhookService::WEBHOOK_DELETED, $result);
        static::assertNull($this->systemConfig->get(Settings::WEBHOOK_ID));
        static::assertNull($this->systemConfig->get(Settings::WEBHOOK_EXECUTE_TOKEN));
    }

    public function testDeregisterWebhookWithoutExistingId(): void
    {
        $result = $this->webhookService->deregisterWebhook(TestDefaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testExecuteWebhook(): void
    {
        $context = Context::createDefaultContext();

        $webhook = new Webhook();
        $webhook->assign(['event_type' => DummyWebhook::EVENT_TYPE]);

        $this->webhookService->executeWebhook($webhook, $context);

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedTransaction = $orderTransactionRepo->getData();

        static::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndIdThrowsException(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, self::THROW_WEBHOOK_ID_INVALID);
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $result = $this->webhookService->registerWebhook(TestDefaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);
    }

    public function testDeregisterWebhookWithInvalidIdThrowsException(): void
    {
        $this->systemConfig->set(Settings::WEBHOOK_ID, WebhookResourceTest::THROW_EXCEPTION_INVALID_ID);
        $this->systemConfig->set(Settings::WEBHOOK_EXECUTE_TOKEN, self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $result = $this->webhookService->deregisterWebhook(null);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
        static::assertNull($this->systemConfig->get(Settings::WEBHOOK_ID, TestDefaults::SALES_CHANNEL));
        static::assertNull($this->systemConfig->get(Settings::WEBHOOK_EXECUTE_TOKEN, TestDefaults::SALES_CHANNEL));
    }

    private function createWebhookResource(SystemConfigService $systemConfigService): WebhookResource
    {
        return new WebhookResource(
            $this->createPayPalClientFactoryWithService($systemConfigService)
        );
    }
}
