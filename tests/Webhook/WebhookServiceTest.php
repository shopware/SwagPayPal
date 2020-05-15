<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\PayPal\Resource\WebhookResource;
use Swag\PayPal\Setting\Service\SettingsServiceInterface;
use Swag\PayPal\Setting\SwagPayPalSettingStruct;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\PayPal\Resource\WebhookResourceTest;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookServiceTest extends TestCase
{
    use ServicesTrait;
    use KernelTestBehaviour;

    public const THROW_WEBHOOK_ID_INVALID = 'webhookIdInvalid';

    public const THROW_WEBHOOK_ALREADY_EXISTS = 'webhookAlreadyExists';

    public const ALREADY_EXISTING_WEBHOOK_ID = 'alreadyExistingTestWebhookId';
    public const ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN = 'testWebhookExecuteToken';

    /**
     * @var EntityRepositoryInterface
     */
    private $orderTransactionRepo;

    protected function setUp(): void
    {
        $definitionRegistry = new DefinitionInstanceRegistryMock([], new DIContainerMock());
        $this->orderTransactionRepo = $definitionRegistry->getRepository(
            (new OrderTransactionDefinition())->getEntityName()
        );
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndId(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);
        $settings->setWebhookExecuteToken(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);

        $settingsService = $this->createSettingsService($settings);
        $result = $this->createWebhookService($settingsService)->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(self::ALREADY_EXISTING_WEBHOOK_ID);

        $settingsService = $this->createSettingsService($settings);
        $result = $this->createWebhookService($settingsService)->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $defaultSettings = $this->createDefaultSettingStruct();
        $settingsService = new SettingsServiceMock($defaultSettings);

        $result = $this->createWebhookService($settingsService)->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        $settings = $settingsService->getSettings();

        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $settings->getWebhookId());
        static::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($settings->getWebhookExecuteToken() ?? ''));
    }

    public function testExecuteWebhook(): void
    {
        $settingsService = $this->createSettingsService();

        $context = Context::createDefaultContext();

        $webhook = new Webhook();
        $webhook->assign(['event_type' => DummyWebhook::EVENT_TYPE]);

        $this->createWebhookService($settingsService)->executeWebhook($webhook, $context);

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedTransaction = $orderTransactionRepo->getData();

        static::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithoutTokenAndIdThrowsException(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookExecuteToken(WebhookResourceTest::TEST_URL_ALREADY_EXISTS);
        $settingsService = $this->createSettingsService($settings);
        $result = $this->createWebhookService($settingsService)->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndIdThrowsException(): void
    {
        $settings = $this->createDefaultSettingStruct();
        $settings->setWebhookId(WebhookResourceTest::THROW_EXCEPTION_INVALID_ID);
        $settings->setWebhookExecuteToken(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN);
        $settingsService = $this->createSettingsService($settings);

        $result = $this->createWebhookService($settingsService)->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);
    }

    private function createSettingsService(?SwagPayPalSettingStruct $settings = null): SettingsServiceMock
    {
        $settings = $settings ?? $this->createDefaultSettingStruct();

        return new SettingsServiceMock($settings);
    }

    private function createWebhookService(SettingsServiceMock $settingsService): WebhookServiceInterface
    {
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;

        return new WebhookService(
            $this->createWebhookResource($settingsService),
            $this->createWebhookRegistry($orderTransactionRepo),
            $settingsService,
            new RouterMock()
        );
    }

    private function createWebhookResource(SettingsServiceInterface $settingsService): WebhookResource
    {
        return new WebhookResource(
            $this->createPayPalClientFactoryWithService($settingsService)
        );
    }
}
