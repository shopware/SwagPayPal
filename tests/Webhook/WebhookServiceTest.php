<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookResourceMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookServiceTest extends TestCase
{
    use ServicesTrait;
    use KernelTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    /**
     * @var DefinitionInstanceRegistryMock
     */
    private $definitionRegistry;

    protected function setUp(): void
    {
        $this->definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $this->orderTransactionRepo = $this->definitionRegistry->getRepository(
            (new OrderTransactionDefinition())->getEntityName()
        );
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndId(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $webhookService = $this->createWebhookService($settings);

        $context = Context::createDefaultContext();

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $webhookService = $this->createWebhookService($settings);

        $context = Context::createDefaultContext();
        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $defaultSettings = $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($defaultSettings);

        $webhookService = $this->createWebhookServiceWithSettingsService($settingsService);

        $context = Context::createDefaultContext();
        $context->addExtension(WebhookResourceMock::RETURN_CREATED_WEBHOOK_ID, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        $settings = $settingsService->getSettings();

        static::assertSame(WebhookResourceMock::CREATED_WEBHOOK_ID, $settings->getWebhookId());
        static::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($settings->getWebhookExecuteToken() ?? ''));
    }

    public function testExecuteWebhook(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();

        $webhook = new Webhook();
        $webhook->assign(['event_type' => DummyWebhook::EVENT_TYPE]);

        $webhookService->executeWebhook($webhook, $context);

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedTransaction = $orderTransactionRepo->getData();

        static::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithoutTokenAndIdThrowsException(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
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

    private function createWebhookService(?SwagPayPalSettingStruct $settings = null): WebhookServiceInterface
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($settings);

        $webhookResourceMock = $this->createWebhookResourceMock($settingsService);
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;

        return new WebhookService(
            $webhookResourceMock,
            $this->createWebhookRegistry($orderTransactionRepo),
            $settingsService,
            new RouterMock()
        );
    }

    private function createWebhookServiceWithSettingsService(SettingsServiceInterface $settingsService): WebhookServiceInterface
    {
        $webhookResourceMock = $this->createWebhookResourceMock($settingsService);
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;

        return new WebhookService(
            $webhookResourceMock,
            $this->createWebhookRegistry($orderTransactionRepo),
            $settingsService,
            new RouterMock()
        );
    }

    private function createWebhookResourceMock(SettingsServiceInterface $settingsService): WebhookResourceMock
    {
        return new WebhookResourceMock(
            $this->createPayPalClientFactoryWithService($settingsService)
        );
    }
}
