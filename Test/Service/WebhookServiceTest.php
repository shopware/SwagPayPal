<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Service;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\PayPal\Client\PayPalClientFactory;
use SwagPayPal\PayPal\Resource\TokenResource;
use SwagPayPal\Service\WebhookService;
use SwagPayPal\Test\Mock\CacheMock;
use SwagPayPal\Test\Mock\DummyCollection;
use SwagPayPal\Test\Mock\PayPal\Client\TokenClientFactoryMock;
use SwagPayPal\Test\Mock\PayPal\Resource\WebhookResourceMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Repositories\SwagPayPalSettingGeneralRepoMock;
use SwagPayPal\Test\Mock\RouterMock;
use SwagPayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use SwagPayPal\Webhook\WebhookRegistry;

class WebhookServiceTest extends TestCase
{
    /**
     * @var SwagPayPalSettingGeneralRepoMock
     */
    private $swagPayPalSettingGeneralRepo;

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    protected function setUp()
    {
        $this->swagPayPalSettingGeneralRepo = new SwagPayPalSettingGeneralRepoMock();
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();

        $result = $webhookService->registerWebhook($context);

        self::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SwagPayPalSettingGeneralRepoMock::PAYPAL_SETTING_WITHOUT_TOKEN, new Entity());

        $result = $webhookService->registerWebhook($context);

        self::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SwagPayPalSettingGeneralRepoMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID, new Entity());

        $result = $webhookService->registerWebhook($context);

        self::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        $updatedSettings = $this->swagPayPalSettingGeneralRepo->getData();

        self::assertSame(SwagPayPalSettingGeneralRepoMock::PAYPAL_SETTING_ID, $updatedSettings['id']);
        self::assertSame(WebhookResourceMock::CREATED_WEBHOOK_ID, $updatedSettings['webhookId']);
        self::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($updatedSettings['webhookExecuteToken']));
    }

    public function testExecuteWebhook(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();

        $webhook = new Webhook();
        $webhook->assign(['event_type' => DummyWebhook::EVENT_TYPE]);

        $webhookService->executeWebhook($webhook, $context);

        $updatedTransaction = $this->orderTransactionRepo->getData();

        self::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithoutTokenAndIdThrowsException(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SwagPayPalSettingGeneralRepoMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID, new Entity());
        $context->addExtension(WebhookResourceMock::THROW_WEBHOOK_ALREADY_EXISTS, new Entity());

        $result = $webhookService->registerWebhook($context);

        self::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndIdThrowsException(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(WebhookResourceMock::THROW_WEBHOOK_ID_INVALID, new Entity());

        $result = $webhookService->registerWebhook($context);

        self::assertSame(WebhookService::WEBHOOK_CREATED, $result);
    }

    private function createWebhookService(): WebhookService
    {
        $webhookResourceMock = $this->createWebhookResourceMock();

        return new WebhookService(
            $webhookResourceMock,
            $this->swagPayPalSettingGeneralRepo,
            new RouterMock(),
            $this->createWebhookRegistry()
        );
    }

    private function createWebhookResourceMock(): WebhookResourceMock
    {
        return new WebhookResourceMock(
            new PayPalClientFactory(
                new TokenResource(new CacheMock(), new TokenClientFactoryMock()),
                $this->swagPayPalSettingGeneralRepo
            )
        );
    }

    private function createWebhookRegistry(): WebhookRegistry
    {
        return new WebhookRegistry(new DummyCollection([$this->createDummyWebhook()]));
    }

    private function createDummyWebhook(): DummyWebhook
    {
        return new DummyWebhook($this->orderTransactionRepo);
    }
}
