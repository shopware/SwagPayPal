<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\PUI\Service\PUIInstructionsFetchService;
use Swag\PayPal\RestApi\V1\Api\Webhook;
use Swag\PayPal\Util\Lifecycle\Method\AbstractMethodData;
use Swag\PayPal\Util\Lifecycle\Method\PaymentMethodDataRegistry;
use Swag\PayPal\Util\Lifecycle\Method\PUIMethodData;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Webhook\Handler\CaptureCompleted;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class CaptureCompletedTest extends AbstractWebhookHandlerTestCase
{
    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_CAPTURE_COMPLETED);
    }

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke(OrderTransactionStates::STATE_PAID, $webhook);
    }

    public function testInvokeWithoutResource(): void
    {
        $this->assertInvokeWithoutResource();
    }

    public function testInvokeWithoutCustomId(): void
    {
        $this->assertInvokeWithoutCustomId(Webhook::RESOURCE_TYPE_CAPTURE);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $orderTransactionId = Uuid::randomHex();
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE, $orderTransactionId);
        $reason = \sprintf('with custom ID "%s" (order transaction ID)', $orderTransactionId);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_CAPTURE_COMPLETED, $webhook, $reason);
    }

    public function testInvokeWithSameInitialState(): void
    {
        $webhook = $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke(OrderTransactionStates::STATE_PAID, $webhook, OrderTransactionStates::STATE_PAID);
    }

    public function testFetchingPUIInstructions(): void
    {
        $transaction = (new OrderTransactionEntity())->assign([
            'id' => 'test-transaction-id',
            'paymentMethodId' => 'test-payment-method-id',
            'order' => (new OrderEntity())->assign(['salesChannelId' => 'test-sales-channel-id']),
        ]);

        $methodDataRegistry = $this->createMock(PaymentMethodDataRegistry::class);

        $methodDataRegistry
            ->expects(static::once())
            ->method('getPaymentMethod')
            ->with(PUIMethodData::class)
            ->willReturn($this->createMock(AbstractMethodData::class));

        $methodDataRegistry
            ->expects(static::once())
            ->method('getEntityIdFromData')
            ->willReturn('test-payment-method-id');

        $instructionsFetchService = $this->createMock(PUIInstructionsFetchService::class);
        $instructionsFetchService
            ->expects(static::once())
            ->method('fetchPUIInstructions')
            ->with($transaction, 'test-sales-channel-id');

        $paymentStatusUtil = $this->createMock(PaymentStatusUtilV2::class);
        $paymentStatusUtil
            ->expects(static::never())
            ->method('applyCaptureState');

        $handler = $this->getMockBuilder(CaptureCompleted::class)
            ->setConstructorArgs([
                $this->orderTransactionRepository,
                new OrderTransactionStateHandler($this->stateMachineRegistry),
                $paymentStatusUtil,
                $methodDataRegistry,
                $instructionsFetchService,
            ])
            ->onlyMethods(['getOrderTransactionV2'])
            ->getMock();

        $handler
            ->expects(static::once())
            ->method('getOrderTransactionV2')
            ->willReturn($transaction);

        $handler->invoke(
            $this->createWebhookV2(Webhook::RESOURCE_TYPE_CAPTURE),
            Context::createDefaultContext()
        );
    }

    protected function createWebhookHandler()
    {
        return new CaptureCompleted(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            $this->getContainer()->get(PaymentStatusUtilV2::class),
            $this->getContainer()->get(PaymentMethodDataRegistry::class),
            $this->getContainer()->get(PUIInstructionsFetchService::class)
        );
    }
}
