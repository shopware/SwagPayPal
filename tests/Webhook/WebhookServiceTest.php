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
use Swag\PayPal\PayPal\Api\Webhook;
use Swag\PayPal\Setting\SwagPayPalSettingGeneralDefinition;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Resource\WebhookResourceMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Repositories\SwagPayPalSettingGeneralRepoMock;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Test\Mock\Setting\Service\SettingsServiceMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookServiceTest extends TestCase
{
    use ServicesTrait;

    /**
     * @var EntityRepositoryInterface
     */
    private $swagPayPalSettingGeneralRepo;

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
        $this->swagPayPalSettingGeneralRepo = $this->definitionRegistry->getRepository(
            (new SwagPayPalSettingGeneralDefinition())->getEntityName()
        );
        $this->orderTransactionRepo = $this->definitionRegistry->getRepository(
            (new OrderTransactionDefinition())->getEntityName()
        );
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
        $context->addExtension(SettingsServiceMock::PAYPAL_SETTING_WITHOUT_TOKEN, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsServiceMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID, new Entity());

        $result = $webhookService->registerWebhook($context);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        /** @var SwagPayPalSettingGeneralRepoMock $swagPayPalSettingGeneralRepo */
        $swagPayPalSettingGeneralRepo = $this->swagPayPalSettingGeneralRepo;
        $updatedSettings = $swagPayPalSettingGeneralRepo->getData();

        static::assertSame(SettingsServiceMock::PAYPAL_SETTING_ID, $updatedSettings['id']);
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

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedTransaction = $orderTransactionRepo->getData();

        static::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithoutTokenAndIdThrowsException(): void
    {
        $webhookService = $this->createWebhookService();

        $context = Context::createDefaultContext();
        $context->addExtension(SettingsServiceMock::PAYPAL_SETTING_WITHOUT_TOKEN_AND_ID, new Entity());
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
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;

        return new WebhookService(
            $webhookResourceMock,
            $this->createWebhookRegistry($orderTransactionRepo),
            new SettingsServiceMock($this->definitionRegistry, new SwagPayPalSettingGeneralDefinition()),
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
