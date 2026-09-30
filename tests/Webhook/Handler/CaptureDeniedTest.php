<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Webhook\Handler;

use PHPUnit\Framework\Attributes\DataProvider;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\Util\PaymentStatusUtilV2;
use Swag\PayPal\Util\PriceFormatter;
use Swag\PayPal\Webhook\Handler\CaptureDenied;
use Swag\PayPal\Webhook\WebhookEventTypes;

/**
 * @internal
 */
#[Package('checkout')]
class CaptureDeniedTest extends AbstractWebhookHandlerTestCase
{
    public function testGetEventType(): void
    {
        $this->assertEventType(WebhookEventTypes::PAYMENT_CAPTURE_DENIED);
    }

    public function testInvoke(): void
    {
        $webhook = $this->createWebhookV2(Event::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke(OrderTransactionStates::STATE_CANCELLED, $webhook);
    }

    public function testInvokeWithoutResource(): void
    {
        $this->assertInvokeWithoutResource();
    }

    public function testInvokeWithoutCustomId(): void
    {
        $this->assertInvokeWithoutCustomId(Event::RESOURCE_TYPE_CAPTURE);
    }

    public function testInvokeWithoutTransaction(): void
    {
        $orderTransactionId = Uuid::randomHex();
        $webhook = $this->createWebhookV2(Event::RESOURCE_TYPE_CAPTURE, $orderTransactionId);
        $reason = \sprintf('with custom ID "%s" (order transaction ID)', $orderTransactionId);
        $this->assertInvokeWithoutTransaction(WebhookEventTypes::PAYMENT_CAPTURE_DENIED, $webhook, $reason);
    }

    public function testInvokeWithSameInitialState(): void
    {
        $webhook = $this->createWebhookV2(Event::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke(OrderTransactionStates::STATE_CANCELLED, $webhook, OrderTransactionStates::STATE_CANCELLED);
    }

    #[DataProvider('cancellationProtectedStates')]
    public function testInvokeDoesNotCancelCapturedTransaction(string $state): void
    {
        $webhook = $this->createWebhookV2(Event::RESOURCE_TYPE_CAPTURE);
        $this->assertInvoke($state, $webhook, $state);
    }

    public function testDeniedCapturePreservesPreviousPartialCapture(): void
    {
        $context = Context::createDefaultContext();
        $transactionId = $this->getTransactionId($context, $this->getContainer(), OrderTransactionStates::STATE_AUTHORIZED);
        $paymentStatusUtil = new PaymentStatusUtilV2(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry),
            new PriceFormatter()
        );
        $capture = (new Capture())->assign([
            'id' => 'completed-capture',
            'status' => 'COMPLETED',
            'final_capture' => false,
            'amount' => ['currency_code' => 'EUR', 'value' => '10.00'],
        ]);

        $paymentStatusUtil->applyCaptureState($transactionId, $capture, $context);
        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PARTIALLY_PAID, $transactionId, $context);

        $webhook = $this->createWebhookV2(Event::RESOURCE_TYPE_CAPTURE, $transactionId);
        $resource = $webhook->getResource();
        static::assertInstanceOf(Capture::class, $resource);
        $resource->assign(['id' => 'denied-capture']);
        $this->webhookHandler->invoke($webhook, $context);

        $this->assertOrderTransactionState(OrderTransactionStates::STATE_PARTIALLY_PAID, $transactionId, $context);
    }

    protected function createWebhookHandler()
    {
        return new CaptureDenied(
            $this->orderTransactionRepository,
            new OrderTransactionStateHandler($this->stateMachineRegistry)
        );
    }
}
