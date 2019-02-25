<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use SwagPayPal\PayPal\Api\Webhook;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\PayPal\Resource\WebhookResourceMock;
use SwagPayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use SwagPayPal\Test\Mock\Repositories\SwagPayPalSettingGeneralRepoMock;
use SwagPayPal\Test\Mock\RouterMock;
use SwagPayPal\Test\Mock\Setting\Service\SettingsProviderMock;
use SwagPayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use SwagPayPal\Webhook\WebhookService;
use SwagPayPal\Webhook\WebhookServiceInterface;

class WebhookServiceTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var SwagPayPalSettingGeneralRepoMock
     */
    private $swagPayPalSettingGeneralRepo;

    /**
     * @var OrderTransactionRepoMock
     */
    private $orderTransactionRepo;

    protected function setUp(): void
    {
        $this->swagPayPalSettingGeneralRepo = new SwagPayPalSettingGeneralRepoMock();
        $this->orderTransactionRepo = new OrderTransactionRepoMock();
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITHOUT_TOKEN, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        $updatedSettings = $this->swagPayPalSettingGeneralRepo->getData();

        static::assertSame(SettingsProviderMock::PAYPAL_SETTING_ID, $updatedSettings['id']);
        static::assertSame(WebhookResourceMock::CREATED_WEBHOOK_ID, $updatedSettings['webhookId']);
        static::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($updatedSettings['webhookExecuteToken']));
    }

    public function testExecuteWebhook(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();

        $webhook = new Webhook();
        $webhook->assign(['event_type' => DummyWebhook::EVENT_TYPE]);

        $webhookService->executeWebhook($webhook, $context);

        $updatedTransaction = $this->orderTransactionRepo->getData();

        static::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithoutTokenAndIdThrowsException(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsProviderMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID, new Entity());
        $context->addExtension(WebhookResourceMock::THROW_WEBHOOK_ALREADY_EXISTS, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndIdThrowsException(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(WebhookResourceMock::THROW_WEBHOOK_ID_INVALID, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);
    }

    private function createWebhookService(): WebhookServiceInterface
    {
        $webhookResourceMock = $this->createWebhookResourceMock();

        return new WebhookService(
            $webhookResourceMock,
            $this->createWebhookRegistry($this->orderTransactionRepo),
            new SettingsProviderMock(),
            $this->swagPayPalSettingGeneralRepo,
            new RouterMock()
        );
    }

    private function createWebhookResourceMock(): WebhookResourceMock
    {
        return new WebhookResourceMock(
            $this->createPayPalClientFactory()
        );
    }
}
