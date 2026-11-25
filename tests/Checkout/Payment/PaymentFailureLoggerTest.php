<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment;

use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\PaymentFailureLogger;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PaymentFailureLogger::class)]
class PaymentFailureLoggerTest extends TestCase
{
    use PayPalLogTrait;

    public function testCancelledByBuyer(): void
    {
        [$orderTransaction, $order] = $this->createOrder();

        PaymentFailureLogger::log($this->createPayPalLogger(), PaymentException::customerCanceled($orderTransaction->getId(), 'cancelled'), $orderTransaction, $order);

        $this->assertPayPalLogged(
            LogCode::CHECKOUT_PAYMENT_CANCELLED,
            Level::Notice,
            [Log::ORDER_NUMBER => '10000', Log::PAYPAL_ORDER_ID => 'PAYPAL-ORDER'],
            'The buyer cancelled the payment of order {orderNumber} at PayPal',
        );
    }

    public function testDeclinedByPayPal(): void
    {
        [$orderTransaction, $order] = $this->createOrder();

        PaymentFailureLogger::log($this->createPayPalLogger(), new OrderFailedException('PAYPAL-ORDER', PaymentStatusV2::ORDER_CAPTURE_DECLINED), $orderTransaction, $order);

        $this->assertPayPalLogged(
            LogCode::CHECKOUT_PAYMENT_DECLINED,
            Level::Notice,
            [Log::ORDER_NUMBER => '10000', Log::SALES_CHANNEL_ID => 'sales-channel-id'],
            'Could not complete the payment of order {orderNumber}: {errorCode}',
        );
    }

    public function testFailure(): void
    {
        [$orderTransaction, $order] = $this->createOrder();

        PaymentFailureLogger::log($this->createPayPalLogger(), new \RuntimeException('failed'), $orderTransaction, $order, 'OTHER-ORDER');

        $record = $this->assertPayPalLogged(
            LogCode::CHECKOUT_PAYMENT_FAILED,
            Level::Error,
            [Log::ORDER_TRANSACTION_ID => $orderTransaction->getId(), Log::PAYPAL_ORDER_ID => 'OTHER-ORDER'],
            'Could not complete the payment of order {orderNumber}: {errorCode}, the payment status of the order is now Failed',
        );
        static::assertSame(\RuntimeException::class, $record->context[Log::EXCEPTION]['class']);
    }

    public function testFailureBeforePayPalIsNotTheBuyers(): void
    {
        [$orderTransaction, $order] = $this->createOrder();

        PaymentFailureLogger::log($this->createPayPalLogger(), PaymentException::customerCanceled($orderTransaction->getId(), 'cancelled'), $orderTransaction, $order, beforePayPal: true);

        $this->assertPayPalLogged(
            LogCode::CHECKOUT_PAYMENT_FAILED,
            Level::Error,
            [Log::ORDER_TRANSACTION_ID => $orderTransaction->getId()],
            'Could not complete the payment of order transaction {orderTransactionId}: {errorCode}, the payment status of the order is now Failed',
        );
    }

    public function testOrderWithoutIdDoesNotThrow(): void
    {
        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());

        $logger = new Logger('paypal', [$handler = new TestHandler()]);

        PaymentFailureLogger::log($logger, new \RuntimeException('failed'), $orderTransaction, new OrderEntity());

        static::assertCount(1, $handler->getRecords());
    }

    /**
     * @return array{0: OrderTransactionEntity, 1: OrderEntity}
     */
    private function createOrder(): array
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setOrderNumber('10000');
        $order->setSalesChannelId('sales-channel-id');

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(Uuid::randomHex());
        $orderTransaction->setCustomFields([SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => 'PAYPAL-ORDER']);

        return [$orderTransaction, $order];
    }
}
