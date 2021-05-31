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
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\RestApi\V1\Resource\WebhookResource;
use Swag\PayPal\Setting\Settings;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\DIContainerMock;
use Swag\PayPal\Test\Mock\PayPal\Client\GuzzleClientMock;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\RouterMock;
use Swag\PayPal\Test\Mock\Webhook\Handler\DummyWebhook;
use Swag\PayPal\Test\RestApi\V1\Resource\WebhookResourceTest;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;

class WebhookServiceTest extends TestCase
{
    use ServicesTrait;

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
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => self::ALREADY_EXISTING_WEBHOOK_ID,
            Settings::WEBHOOK_EXECUTE_TOKEN => self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN,
        ]);

        $result = $this->createWebhookService($settings)->registerWebhook(null);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithoutTokenButWithId(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => self::ALREADY_EXISTING_WEBHOOK_ID,
        ]);

        $result = $this->createWebhookService($settings)->registerWebhook(null);

        static::assertSame(WebhookService::WEBHOOK_UPDATED, $result);
    }

    public function testRegisterWebhookWithoutTokenAndId(): void
    {
        $settings = $this->createDefaultSystemConfig();
        $result = $this->createWebhookService($settings)->registerWebhook(null);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);

        static::assertSame(GuzzleClientMock::TEST_WEBHOOK_ID, $settings->getString(Settings::WEBHOOK_ID));
        static::assertSame(WebhookService::PAYPAL_WEBHOOK_TOKEN_LENGTH, \mb_strlen($settings->getString(Settings::WEBHOOK_EXECUTE_TOKEN)));
    }

    public function testDeregisterWebhookWithExistingInheritedId(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => self::ALREADY_EXISTING_WEBHOOK_ID,
            Settings::WEBHOOK_EXECUTE_TOKEN => self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN,
        ]);

        $result = $this->createWebhookService($settings)->deregisterWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
        static::assertSame(self::ALREADY_EXISTING_WEBHOOK_ID, $settings->get(Settings::WEBHOOK_ID));
        static::assertSame(self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN, $settings->get(Settings::WEBHOOK_EXECUTE_TOKEN));
    }

    public function testDeregisterWebhookWithExistingId(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => self::ALREADY_EXISTING_WEBHOOK_ID,
            Settings::WEBHOOK_EXECUTE_TOKEN => self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN,
        ]);

        $result = $this->createWebhookService($settings)->deregisterWebhook(null);

        static::assertSame(WebhookService::WEBHOOK_DELETED, $result);
        static::assertNull($settings->get(Settings::WEBHOOK_ID));
        static::assertNull($settings->get(Settings::WEBHOOK_EXECUTE_TOKEN));
    }

    public function testDeregisterWebhookWithoutExistingId(): void
    {
        $settings = $this->createDefaultSystemConfig();
        $result = $this->createWebhookService($settings)->deregisterWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testExecuteWebhook(): void
    {
        $context = Context::createDefaultContext();

        $webhook = new Webhook();
        $webhook->assign(['event_type' => DummyWebhook::EVENT_TYPE]);

        $this->createWebhookService($this->createDefaultSystemConfig())->executeWebhook($webhook, $context);

        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;
        $updatedTransaction = $orderTransactionRepo->getData();

        static::assertTrue($updatedTransaction[DummyWebhook::ORDER_TRANSACTION_UPDATE_DATA_KEY]);
    }

    public function testRegisterWebhookWithoutTokenAndIdThrowsException(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_EXECUTE_TOKEN => WebhookResourceTest::TEST_URL_ALREADY_EXISTS,
        ]);
        $result = $this->createWebhookService($settings)->registerWebhook(null);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
    }

    public function testRegisterWebhookWithAlreadyExistingTokenAndIdThrowsException(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => WebhookResourceTest::THROW_EXCEPTION_INVALID_ID,
            Settings::WEBHOOK_EXECUTE_TOKEN => self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN,
        ]);

        $result = $this->createWebhookService($settings)->registerWebhook(Defaults::SALES_CHANNEL);

        static::assertSame(WebhookService::WEBHOOK_CREATED, $result);
    }

    public function testDeregisterWebhookWithInvalidIdThrowsException(): void
    {
        $settings = $this->createDefaultSystemConfig([
            Settings::WEBHOOK_ID => WebhookResourceTest::THROW_EXCEPTION_INVALID_ID,
            Settings::WEBHOOK_EXECUTE_TOKEN => self::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN,
        ]);

        $result = $this->createWebhookService($settings)->deregisterWebhook(null);

        static::assertSame(WebhookService::NO_WEBHOOK_ACTION_REQUIRED, $result);
        static::assertNull($settings->get(Settings::WEBHOOK_ID, Defaults::SALES_CHANNEL));
        static::assertNull($settings->get(Settings::WEBHOOK_EXECUTE_TOKEN, Defaults::SALES_CHANNEL));
    }

    private function createWebhookService(SystemConfigService $systemConfigService): WebhookServiceInterface
    {
        /** @var OrderTransactionRepoMock $orderTransactionRepo */
        $orderTransactionRepo = $this->orderTransactionRepo;

        return new WebhookService(
            $this->createWebhookResource($systemConfigService),
            $this->createWebhookRegistry($orderTransactionRepo),
            $systemConfigService,
            new RouterMock()
        );
    }

    private function createWebhookResource(SystemConfigService $systemConfigService): WebhookResource
    {
        return new WebhookResource(
            $this->createPayPalClientFactoryWithService($systemConfigService)
        );
    }
}
