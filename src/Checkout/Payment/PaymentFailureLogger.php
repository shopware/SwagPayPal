<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Payment;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Util\Log\FailureClassifier;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * Logs why a payment failed, once per failure, at the level of who has to act on it.
 *
 * @internal
 */
#[Package('checkout')]
final class PaymentFailureLogger
{
    /**
     * @param string|null $paypalOrderId defaults to the PayPal order ID stored at the order transaction
     * @param bool $beforePayPal nothing reached PayPal yet, so it can't be the buyer's failure
     */
    public static function log(
        LoggerInterface $logger,
        \Throwable $e,
        OrderTransactionEntity $orderTransaction,
        OrderEntity $order,
        ?string $paypalOrderId = null,
        bool $beforePayPal = false,
    ): void {
        if ($paypalOrderId === null) {
            $storedPayPalOrderId = $orderTransaction->getCustomFieldsValue(SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID);
            $paypalOrderId = \is_string($storedPayPalOrderId) ? $storedPayPalOrderId : null;
        }

        if ($beforePayPal) {
            $logger->error(
                'Could not complete the payment of order transaction {orderTransactionId}: {errorCode}, the payment status of the order is now Failed',
                Log::context(
                    orderTransactionId: $orderTransaction->getId(),
                    paypalOrderId: $paypalOrderId,
                    exception: $e,
                    code: LogCode::CHECKOUT_PAYMENT_FAILED,
                ),
            );

            return;
        }

        // the struct's transaction doesn't carry its order
        $orderId = $orderNumber = $salesChannelId = null;
        try {
            $orderId = $order->getId();
            $orderNumber = $order->getOrderNumber();
            $salesChannelId = $order->getSalesChannelId();
        } catch (\Throwable) {
            // the getters of an order loaded without some fields throw
        }

        $context = [
            Log::ORDER_TRANSACTION => $orderTransaction,
            Log::ORDER_ID => $orderId,
            Log::ORDER_NUMBER => $orderNumber,
            Log::SALES_CHANNEL_ID => $salesChannelId,
            Log::ORDER_TRANSACTION_ID => $orderTransaction->getId(),
            Log::PAYPAL_ORDER_ID => $paypalOrderId,
            Log::EXCEPTION => $e,
        ];

        if (FailureClassifier::isCancelledByBuyer($e)) {
            $logger->notice('The buyer cancelled the payment of order {orderNumber} at PayPal', Log::context(...$context, code: LogCode::CHECKOUT_PAYMENT_CANCELLED));

            return;
        }

        if (FailureClassifier::isCausedByBuyer($e)) {
            $logger->notice('Could not complete the payment of order {orderNumber}: {errorCode}', Log::context(...$context, code: LogCode::CHECKOUT_PAYMENT_DECLINED));

            return;
        }

        $logger->error(
            'Could not complete the payment of order {orderNumber}: {errorCode}, the payment status of the order is now Failed',
            Log::context(...$context, code: LogCode::CHECKOUT_PAYMENT_FAILED),
        );
    }
}
