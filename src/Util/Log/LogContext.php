<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V1\Webhook\Event;
use Shopware\PayPalSDK\Struct\V2\Common\Money;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Authorization;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Refund;

/**
 * Expands the objects under the object keys into the scalar keys every log line shares.
 *
 * @internal
 */
#[Package('checkout')]
final class LogContext
{
    /**
     * The classes each object key takes, `LogCallRule` checks them at the call sites.
     */
    public const OBJECT_KEYS = [
        LogKey::ORDER_TRANSACTION => [OrderTransactionEntity::class],
        LogKey::PAYPAL_ORDER => [Order::class],
        LogKey::PAYMENT => [Capture::class, Refund::class, Authorization::class],
        LogKey::WEBHOOK_EVENT => [Event::class],
    ];

    /**
     * Values passed at the call site win over expanded ones, and a payment's amount wins over its PayPal order's.
     *
     * @param array<array-key, mixed> $context
     *
     * @return array<array-key, mixed>
     */
    public static function expand(array $context): array
    {
        $expanded = [];

        $event = $context[LogKey::WEBHOOK_EVENT] ?? null;
        if ($event instanceof Event) {
            $expanded = [...$expanded, ...self::webhookEvent($event)];
        }

        $transaction = $context[LogKey::ORDER_TRANSACTION] ?? null;
        if ($transaction instanceof OrderTransactionEntity) {
            $expanded = [...$expanded, ...self::orderTransaction($transaction)];
        }

        $paypalOrder = $context[LogKey::PAYPAL_ORDER] ?? null;
        if ($paypalOrder instanceof Order) {
            $expanded = [...$expanded, ...self::paypalOrder($paypalOrder)];
        }

        $payment = $context[LogKey::PAYMENT] ?? null;
        if ($payment instanceof Capture || $payment instanceof Refund || $payment instanceof Authorization) {
            $expanded = [...$expanded, ...self::payment($payment)];
        }

        $callSiteValues = \array_filter(\array_diff_key($context, self::OBJECT_KEYS), static fn (mixed $value): bool => $value !== null);

        return [...$expanded, ...$callSiteValues];
    }

    /**
     * @return array<string, scalar>
     */
    private static function orderTransaction(OrderTransactionEntity $transaction): array
    {
        $context = [LogKey::ORDER_TRANSACTION_ID => $transaction->getId()];

        if ($order = $transaction->getOrder()) {
            $context = [...$context, ...self::order($order)];
        }

        if (($paymentMethod = $transaction->getPaymentMethod()) && $technicalName = $paymentMethod->getTechnicalName()) {
            $context[LogKey::PAYMENT_METHOD] = $technicalName;
        }

        return $context;
    }

    /**
     * @return array<string, scalar>
     */
    private static function order(OrderEntity $order): array
    {
        $context = [LogKey::ORDER_ID => $order->getId()];

        if ($order->getOrderNumber() !== null) {
            $context[LogKey::ORDER_NUMBER] = $order->getOrderNumber();
        }

        $context[LogKey::SALES_CHANNEL_ID] = $order->getSalesChannelId();

        return $context;
    }

    /**
     * @return array<string, scalar>
     */
    private static function paypalOrder(Order $order): array
    {
        $context = [];

        if ($order->isset('id')) {
            $context[LogKey::PAYPAL_ORDER_ID] = $order->getId();
        }

        if ($order->isset('intent')) {
            $context[LogKey::INTENT] = $order->getIntent();
        }

        if (!$order->isset('purchaseUnits') || !$purchaseUnit = $order->getPurchaseUnits()->first()) {
            return $context;
        }

        if ($purchaseUnit->isset('amount')) {
            $context = [...$context, ...self::amount($purchaseUnit->getAmount())];
        }

        if ($purchaseUnit->isset('invoiceId') && $purchaseUnit->getInvoiceId() !== null) {
            $context[LogKey::INVOICE_ID] = $purchaseUnit->getInvoiceId();
        }

        return $context;
    }

    /**
     * @return array<string, string>
     */
    private static function amount(?Money $amount): array
    {
        $context = [];

        if ($amount?->isset('value')) {
            $context[LogKey::AMOUNT] = $amount->getValue();
        }

        if ($amount?->isset('currencyCode')) {
            $context[LogKey::CURRENCY] = $amount->getCurrencyCode();
        }

        return $context;
    }

    /**
     * Every getter is guarded: a minimal PayPal response may lack fields, and logging must never throw after money moved.
     *
     * @return array<string, string>
     */
    private static function payment(Capture|Refund|Authorization $payment): array
    {
        $context = [];
        [$idKey, $statusKey] = match (true) {
            $payment instanceof Capture => [LogKey::CAPTURE_ID, LogKey::CAPTURE_STATUS],
            $payment instanceof Refund => [LogKey::REFUND_ID, LogKey::REFUND_STATUS],
            $payment instanceof Authorization => [LogKey::AUTHORIZATION_ID, LogKey::AUTHORIZATION_STATUS],
        };

        if ($payment->isset('id')) {
            $context[$idKey] = $payment->getId();
        }

        if ($payment->isset('status')) {
            $context[$statusKey] = $payment->getStatus();
        }

        return [...$context, ...self::amount($payment->isset('amount') ? $payment->getAmount() : null)];
    }

    /**
     * @return array<string, scalar>
     */
    private static function webhookEvent(Event $event): array
    {
        $context = [];

        if ($event->isset('id')) {
            $context[LogKey::WEBHOOK_EVENT_ID] = $event->getId();
        }

        if ($event->isset('eventType')) {
            $context[LogKey::WEBHOOK_EVENT_TYPE] = $event->getEventType();
        }

        return $context;
    }
}
