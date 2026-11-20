<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Amount;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Authorization;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Refund;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnitCollection;
use Swag\PayPal\Util\Log\LogContext;
use Swag\PayPal\Util\Log\LogKey;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(LogContext::class)]
class LogContextTest extends TestCase
{
    public function testOrderTransactionMinimal(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');

        static::assertSame(['orderTransactionId' => 'transaction-id'], LogContext::expand([LogKey::ORDER_TRANSACTION => $transaction]));
    }

    public function testOrderTransactionFull(): void
    {
        $order = $this->createOrder();
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTechnicalName('swag_paypal_acdc');
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setOrder($order);
        $transaction->setPaymentMethod($paymentMethod);

        static::assertSame([
            'orderTransactionId' => 'transaction-id',
            'orderId' => $order->getId(),
            'orderNumber' => '10023',
            'salesChannelId' => $order->getSalesChannelId(),
            'paymentMethod' => 'swag_paypal_acdc',
        ], LogContext::expand([LogKey::ORDER_TRANSACTION => $transaction]));
    }

    public function testOrderTransactionWithoutOrderNumber(): void
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setSalesChannelId(Uuid::randomHex());
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setOrder($order);

        static::assertArrayNotHasKey('orderNumber', LogContext::expand([LogKey::ORDER_TRANSACTION => $transaction]));
    }

    public function testPaypalOrder(): void
    {
        static::assertSame([
            'paypalOrderId' => '5O190127TN364715T',
            'intent' => 'CAPTURE',
            'amount' => '10.00',
            'currency' => 'EUR',
            'invoiceId' => 'INV-1',
        ], LogContext::expand([LogKey::PAYPAL_ORDER => $this->createPaypalOrder()]));
    }

    public function testPaypalOrderSkipsUnsetValues(): void
    {
        static::assertSame(['intent' => 'CAPTURE'], LogContext::expand([LogKey::PAYPAL_ORDER => new Order()]));

        $order = new Order();
        $order->setId('ID');
        $order->setPurchaseUnits(new PurchaseUnitCollection([new PurchaseUnit()]));

        static::assertSame(['paypalOrderId' => 'ID', 'intent' => 'CAPTURE'], LogContext::expand([LogKey::PAYPAL_ORDER => $order]));
    }

    public function testWebhookEvent(): void
    {
        $event = new Event();
        static::assertSame([], LogContext::expand([LogKey::WEBHOOK_EVENT => $event]));

        $event->assign(['id' => 'WH-1', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED']);

        static::assertSame([
            'webhookEventId' => 'WH-1',
            'webhookEventType' => 'PAYMENT.CAPTURE.COMPLETED',
        ], LogContext::expand([LogKey::WEBHOOK_EVENT => $event]));
    }

    public function testPayment(): void
    {
        static::assertSame([
            'captureId' => 'CAP-1',
            'captureStatus' => 'COMPLETED',
            'amount' => '5.00',
            'currency' => 'EUR',
        ], LogContext::expand([LogKey::PAYMENT => $this->createCapture()]));

        // a minimal response only has the ID and status, an empty one must not throw either
        static::assertSame(['refundId' => 'REF-1'], LogContext::expand([LogKey::PAYMENT => (new Refund())->assign(['id' => 'REF-1'])]));
        static::assertSame([], LogContext::expand([LogKey::PAYMENT => new Authorization()]));
        static::assertSame([], LogContext::expand([LogKey::PAYMENT => null]));
    }

    public function testValuesOfTheCallSiteWinAndThePaymentWinsOverItsPaypalOrder(): void
    {
        static::assertSame([
            'paypalOrderId' => 'EXPLICIT',
            'intent' => 'CAPTURE',
            'amount' => '5.00',
            'currency' => 'EUR',
            'invoiceId' => 'INV-1',
            'captureId' => 'CAP-1',
            'captureStatus' => 'COMPLETED',
        ], LogContext::expand([
            LogKey::PAYPAL_ORDER_ID => 'EXPLICIT',
            LogKey::PAYPAL_ORDER => $this->createPaypalOrder(),
            LogKey::PAYMENT => $this->createCapture(),
        ]));
    }

    public function testNullAtTheCallSiteDoesNotHideAnExpandedValue(): void
    {
        static::assertSame(
            ['paypalOrderId' => '5O190127TN364715T', 'intent' => 'CAPTURE', 'amount' => '10.00', 'currency' => 'EUR', 'invoiceId' => 'INV-1'],
            LogContext::expand([LogKey::PAYPAL_ORDER_ID => null, LogKey::PAYPAL_ORDER => $this->createPaypalOrder()]),
        );
    }

    public function testObjectKeysWithOtherValuesAreDropped(): void
    {
        static::assertSame(
            ['code' => 'CODE'],
            LogContext::expand([LogKey::CODE => 'CODE', LogKey::PAYPAL_ORDER => new OrderEntity(), LogKey::PAYMENT => 'CAP-1']),
        );
    }

    private function createOrder(): OrderEntity
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setOrderNumber('10023');
        $order->setSalesChannelId(Uuid::randomHex());

        return $order;
    }

    private function createPaypalOrder(): Order
    {
        $amount = new Amount();
        $amount->setValue('10.00');
        $amount->setCurrencyCode('EUR');
        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setAmount($amount);
        $purchaseUnit->setInvoiceId('INV-1');
        $order = new Order();
        $order->setId('5O190127TN364715T');
        $order->setIntent('CAPTURE');
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));

        return $order;
    }

    private function createCapture(): Capture
    {
        return (new Capture())->assign([
            'id' => 'CAP-1',
            'status' => 'COMPLETED',
            'amount' => ['value' => '5.00', 'currency_code' => 'EUR'],
        ]);
    }
}
