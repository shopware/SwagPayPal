<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\TestCaseBase\DatabaseTransactionBehaviour;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Helper\OrderTransactionTrait;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Test\Mock\Repositories\DefinitionInstanceRegistryMock;
use Swag\PayPal\Test\Webhook\_fixtures\WebhookDataFixture;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Swag\PayPal\Util\Log\PaymentStatusChangeSubscriber;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Handler\CaptureCompleted;
use Swag\PayPal\Webhook\Registration\WebhookUrlBuilder;
use Swag\PayPal\Webhook\WebhookController;
use Swag\PayPal\Webhook\WebhookService;
use Swag\PayPal\Webhook\WebhookServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentStatusChangeSubscriber::class)]
class WebhookControllerPaymentStatusTest extends TestCase
{
    use DatabaseTransactionBehaviour;

    use KernelTestBehaviour;
    use OrderTransactionTrait;
    use PayPalLogTrait;

    private WebhookController $controller;

    private PaymentStatusChangeSubscriber $subscriber;

    protected function setUp(): void
    {
        $container = $this->getContainer();
        /** @var EntityRepository $orderTransactionRepository */
        $orderTransactionRepository = $container->get('order_transaction.repository');

        $handler = new CaptureCompleted(
            $orderTransactionRepository,
            new OrderTransactionStateHandler($container->get(StateMachineRegistry::class)),
            $container->get(PaymentStatusUtilV2::class),
            $container->get(PaymentMethodDataRegistry::class),
            $container->get(PUIInstructionsFetchService::class),
            $this->createMock(TransactionDataService::class),
            $this->createMock(OrderResource::class),
        );

        $webhookService = $this->createMock(WebhookServiceInterface::class);
        $webhookService->method('executeWebhook')->willReturnCallback(
            static fn (Event $webhook, Context $context) => $handler->invoke($webhook, $context)
        );

        $systemConfigRepo = (new DefinitionInstanceRegistryMock([], $this->createMock(ContainerInterface::class)))
            ->getRepository('system_config');

        $logger = $this->createPayPalLogger();
        $this->subscriber = new PaymentStatusChangeSubscriber($logger, $orderTransactionRepository, $container->get(PaymentMethodDataRegistry::class));
        $this->getContainer()->get('event_dispatcher')->addSubscriber($this->subscriber);
        $this->controller = new WebhookController(
            $logger,
            $webhookService,
            $systemConfigRepo,
            new WebhookUrlBuilder($this->createMock(RouterInterface::class), new RequestStack(), ''),
            new RequestStack(),
        );
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('event_dispatcher')->removeSubscriber($this->subscriber);
    }

    public function testLogsPaymentStatusChange(): void
    {
        $context = Context::createDefaultContext();
        $transactionId = $this->getTransactionId($context, $this->getContainer());

        $response = $this->controller->executeWebhook($this->createRequest($transactionId), $context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PAID, $transactionId, $context);

        $record = $this->assertPayPalLogged(LogCode::PAYMENT_STATUS_CHANGED, Level::Info, [
            Log::ORDER_TRANSACTION_ID => $transactionId,
            Log::FROM_STATE => OrderTransactionStates::STATE_OPEN,
            Log::TO_STATE => OrderTransactionStates::STATE_PAID,
        ]);
        static::assertArrayHasKey(Log::ORDER_NUMBER, $record->context);
        static::assertCount(1, $this->getPayPalLogRecords(LogCode::WEBHOOK_EVENT_PROCESSED));
    }

    public function testLogsNoPaymentStatusChangeWhenNothingChanged(): void
    {
        $context = Context::createDefaultContext();
        $transactionId = $this->getTransactionId($context, $this->getContainer(), OrderTransactionStates::STATE_PAID);

        $response = $this->controller->executeWebhook($this->createRequest($transactionId), $context);

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertPayPalNotLogged(LogCode::PAYMENT_STATUS_CHANGED);
        static::assertCount(1, $this->getPayPalLogRecords(LogCode::WEBHOOK_EVENT_PROCESSED));
    }

    private function createRequest(string $transactionId): Request
    {
        return new Request(
            [WebhookService::PAYPAL_WEBHOOK_TOKEN_NAME => WebhookServiceTest::ALREADY_EXISTING_WEBHOOK_EXECUTE_TOKEN],
            [
                'id' => WebhookDataFixture::get()['id'],
                'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
                'resource_type' => Event::RESOURCE_TYPE_CAPTURE,
                'resource_version' => '2.0',
                'resource' => [
                    'custom_id' => \json_encode(['orderTransactionId' => $transactionId], \JSON_THROW_ON_ERROR),
                    'final_capture' => true,
                ],
            ],
        );
    }
}
