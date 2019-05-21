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
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\PayPal\Resource\WebhookResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookReturnAlreadyExistsResourceMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookReturnCreatedResourceMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookThrowAlreadyExistsExceptionResourceMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookThrowIdInvalidExceptionResourceMock;
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
        $settings->setWebhookId(WebhookReturnCreatedResourceMock::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(WebhookReturnCreatedResourceMock::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $settingsService = $this->createSettingsService($settings);
        $webhookResourceMock = $this->createWebhookReturnCreatedResourceMock($settingsService);
        $webhookService = $this->createWebhookService($settingsService, $webhookResourceMock);

        $result = $webhookService->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookReturnCreatedResourceMock::ALREADY_EXISTING_WEBHOOK_ID);

        $settingsService = $this->createSettingsService($settings);
        $webhookResourceMock = new WebhookReturnAlreadyExistsResourceMock(
            $this->createPayPalClientFactoryWithService($settingsService)
        );
        $webhookService = $this->createWebhookService($settingsService, $webhookResourceMock);

        $result = $webhookService->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $defaultSettings = $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($defaultSettings);

        $webhookResourceMock = $this->createWebhookReturnCreatedResourceMock($settingsService);
        $webhookService = $this->createWebhookService($settingsService, $webhookResourceMock);

        $result = $webhookService->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        $settings = $settingsService->getSettings();

        static::assertSame(WebhookReturnCreatedResourceMock::CREATED_WEBHOOK_ID, $settings->getWebhookId());
        static::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($settings->getWebhookExecuteToken() ?? ''));
    }

    public function testExecuteWebhook(): void
    {
        $settingsService = $this->createSettingsService();
        $webhookResourceMock = $this->createWebhookReturnCreatedResourceMock($settingsService);
        $webhookService = $this->createWebhookService($settingsService, $webhookResourceMock);

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
        $settingsService = $this->createSettingsService();
        $webhookResourceMock = new WebhookThrowAlreadyExistsExceptionResourceMock(
            $this->createPayPalClientFactoryWithService($settingsService)
        );
        $webhookService = $this->createWebhookService($settingsService, $webhookResourceMock);

        $context = Context::createDefaultContext();
        $context->addExtension(WebhookReturnCreatedResourceMock::THROW_WEBHOOK_ALREADY_EXISTS, new Entity());

        $result = $webhookService->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndIdThrowsException(): void
    {
        $settingsService = $this->createSettingsService();
        $webhookResourceMock = new WebhookThrowIdInvalidExceptionResourceMock(
            $this->createPayPalClientFactoryWithService($settingsService)
        );
        $webhookService = $this->createWebhookService($settingsService, $webhookResourceMock);

        $context = Context::createDefaultContext();
        $context->addExtension(WebhookReturnCreatedResourceMock::THROW_WEBHOOK_ID_INVALID, new Entity());

        $result = $webhookService->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);
    }

    private function createSettingsService(?SwagPayPalSettingStruct $settings = null)
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();

        return new SettingsServiceMock($settings);
    }

    private function createWebhookService($settingsService, WebhookResource $resource): WebhookServiceInterface
    {
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;

        return new WebhookService(
            $resource,
            $this->createWebhookRegistry($orderTransactionRepo),
            $settingsService,
            new RouterMock()
        );
    }

    private function createWebhookReturnCreatedResourceMock(SettingsServiceInterface $settingsService): WebhookReturnCreatedResourceMock
    {
        return new WebhookReturnCreatedResourceMock(
            $this->createPayPalClientFactoryWithService($settingsService)
        );
    }
}
