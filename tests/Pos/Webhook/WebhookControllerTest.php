<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPalTestPosUtil;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\Pos\Api\Service\Converter\UuidConverter;
use Swag\PayPal\Pos\Resource\SubscriptionResource;
use Swag\PayPal\Pos\Util\PosSalesChannelTrait;
use Swag\PayPal\Pos\Webhook\Exception\WebhookIdInvalidException;
use Swag\PayPal\Pos\Webhook\Exception\WebhookNotRegisteredException;
use Swag\PayPal\Pos\Webhook\WebhookController;
use Swag\PayPal\Pos\Webhook\WebhookRegistry;
use Swag\PayPal\Pos\Webhook\WebhookService;
use Swag\PayPal\Test\Pos\Helper\SalesChannelTrait;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookRegisterFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookUnregisterFixture;
use Swag\PayPal\Test\Pos\Mock\Client\_fixtures\WebhookUpdateFixture;
use Swag\PayPal\Test\Pos\Mock\Client\PosClientFactoryMock;
use Swag\PayPal\Test\Pos\Mock\Repositories\SalesChannelRepoMock;
use Swag\PayPal\Test\Pos\Webhook\_fixtures\InventoryChangeFixture;
use Swag\PayPal\Test\Pos\Webhook\_fixtures\TestMessageFixture;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Router;

/**
 * @internal
 */
class WebhookControllerTest extends TestCase
{
    use KernelTestBehaviour;
    use PosSalesChannelTrait;
    use SalesChannelTrait;

    private const INVALID_EVENT_NAME = 'ThisIsNotAnEvent';

    private Context $context;

    private SalesChannelEntity $salesChannel;

    private WebhookController $webhookController;

    protected function setUp(): void
    {
        $this->context = Context::createDefaultContext();

        $this->salesChannel = $this->getSalesChannel($this->context);

        $webhookRegistry = new WebhookRegistry(new \ArrayObject([]));
        $salesChannelRepository = new SalesChannelRepoMock();
        $salesChannelRepository->addMockEntity($this->salesChannel);

        /** @var Router $router */
        $router = $this->getContainer()->get('router');

        $webhookService = new WebhookService(
            new SubscriptionResource(new PosClientFactoryMock()),
            $webhookRegistry,
            $salesChannelRepository,
            $this->getContainer()->get(SystemConfigService::class),
            new UuidConverter(),
            $router
        );

        $this->webhookController = new WebhookController(
            new NullLogger(),
            $webhookService,
            $salesChannelRepository
        );
    }

    public function testExecuteInvalidSignature(): void
    {
        $request = new Request([], InventoryChangeFixture::getWebhookFixture());
        $request->headers->add(['x-izettle-signature' => Uuid::randomHex()]);

        $this->expectException(UnauthorizedHttpException::class);
        $this->webhookController->executeWebhook(TestDefaults::SALES_CHANNEL, $request, $this->context);
    }

    public function testExecuteNoSignature(): void
    {
        $request = new Request([], InventoryChangeFixture::getWebhookFixture());

        $this->expectException(UnauthorizedHttpException::class);
        $this->webhookController->executeWebhook(TestDefaults::SALES_CHANNEL, $request, $this->context);
    }

    public function testExecuteUnknownEventName(): void
    {
        $request = new Request([], InventoryChangeFixture::getWebhookFixture(self::INVALID_EVENT_NAME));
        $request->headers->add(['x-izettle-signature' => InventoryChangeFixture::getSignature()]);

        $this->expectException(BadRequestHttpException::class);
        $this->webhookController->executeWebhook(TestDefaults::SALES_CHANNEL, $request, $this->context);
    }

    public function testExecuteTestMessage(): void
    {
        $request = new Request([], TestMessageFixture::getWebhookFixture());

        $response = $this->webhookController->executeWebhook(TestDefaults::SALES_CHANNEL, $request, $this->context);
        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testExecuteInvalidSalesChannel(): void
    {
        $request = new Request([], InventoryChangeFixture::getWebhookFixture());
        $request->headers->add(['x-izettle-signature' => InventoryChangeFixture::getSignature()]);

        $this->expectException(WebhookIdInvalidException::class);
        $this->webhookController->executeWebhook(Uuid::randomHex(), $request, $this->context);
    }

    public function testExecuteUnregisteredSalesChannel(): void
    {
        $this->getPosSalesChannel($this->salesChannel)->setWebhookSigningKey(null);

        $request = new Request([], InventoryChangeFixture::getWebhookFixture());
        $request->headers->add(['x-izettle-signature' => InventoryChangeFixture::getSignature()]);

        $this->expectException(WebhookNotRegisteredException::class);
        $this->webhookController->executeWebhook(TestDefaults::SALES_CHANNEL, $request, $this->context);
    }

    public function testRegister(): void
    {
        $this->getPosSalesChannel($this->salesChannel)->setWebhookSigningKey(null);

        $this->webhookController->registerWebhook(TestDefaults::SALES_CHANNEL, $this->context);

        static::assertSame(WebhookRegisterFixture::WEBHOOK_SIGNING_KEY, $this->getPosSalesChannel($this->salesChannel)->getWebhookSigningKey());
    }

    public function testRegisterExisting(): void
    {
        $this->webhookController->registerWebhook(TestDefaults::SALES_CHANNEL, $this->context);

        static::assertTrue(WebhookUpdateFixture::$sent);
    }

    public function testUnregister(): void
    {
        $this->webhookController->unregisterWebhook(TestDefaults::SALES_CHANNEL, $this->context);

        static::assertTrue(WebhookUnregisterFixture::$sent);
        static::assertNull($this->getPosSalesChannel($this->salesChannel)->getWebhookSigningKey());
    }
}
