<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Swag\PayPal\PayPal\Api\Payment;
use Swag\PayPal\PayPal\PaymentStatus;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\CreateResponseFixture;

class PaymentResourceTest extends TestCase
{
    use PaymentTransactionTrait,
        ServicesTrait;

    public const CAPTURED_ORDER_PAYMENT_ID = 'testCapturedOrderPaymentId';
    public const ORDER_PAYMENT_ID = 'testOrderPaymentId';

    public const AUTHORIZE_PAYMENT_ID = 'testAuthorizePaymentId';

    public const SALE_WITH_REFUND_PAYMENT_ID = 'testSaleWithRefundPaymentId';
    private const TEST_PAYMENT_ID = 'testPaymentId';

    public function testCreate(): void
    {
        $context = Context::createDefaultContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $payment = $this->createPaymentBuilder()->getPayment($paymentTransaction, $context);
        $createdPayment = $this->createPaymentResource()->create($payment, $context);

        static::assertInstanceOf(Payment::class, $createdPayment);
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $createdPayment->getId());
        $transaction = $createdPayment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        $link = $createdPayment->getLinks()[1];
        static::assertInstanceOf(Payment\Link::class, $link);
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $link->getHref());
    }

    public function testExecuteSale(): void
    {
        $context = Context::createDefaultContext();
        $executedPayment = $this->createPaymentResource()->execute('testPayerId', self::TEST_PAYMENT_ID, $context);

        static::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);

        $sale = $transaction->getRelatedResources()[0]->getSale();
        static::assertNotNull($sale);
        if ($sale !== null) {
            static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $sale->getState());
        }
    }

    public function testExecuteAuthorize(): void
    {
        $context = Context::createDefaultContext();
        $executedPayment = $this->createPaymentResource()->execute(
            ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE,
            self::TEST_PAYMENT_ID,
            $context
        );

        static::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);

        $authorization = $transaction->getRelatedResources()[0]->getAuthorization();
        static::assertNotNull($authorization);
        if ($authorization !== null) {
            static::assertSame(PaymentStatus::PAYMENT_AUTHORIZED, $authorization->getState());
        }
    }

    public function testExecuteOrder(): void
    {
        $context = Context::createDefaultContext();
        $executedPayment = $this->createPaymentResource()->execute(
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER,
            self::TEST_PAYMENT_ID,
            $context
        );

        static::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);

        $order = $transaction->getRelatedResources()[0]->getOrder();
        static::assertNotNull($order);
        if ($order !== null) {
            static::assertSame(PaymentStatus::PAYMENT_PENDING, $order->getState());
        }
    }

    public function testGetSale(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::TEST_PAYMENT_ID, $context);

        static::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);

        $sale = $transaction->getRelatedResources()[0]->getSale();
        static::assertNotNull($sale);
        if ($sale !== null) {
            static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $sale->getState());
        }
    }

    public function testGetSaleWithRefund(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::SALE_WITH_REFUND_PAYMENT_ID, $context);

        static::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);

        $sale = $transaction->getRelatedResources()[0]->getSale();
        static::assertNotNull($sale);
        if ($sale !== null) {
            static::assertSame(PaymentStatus::PAYMENT_PARTIALLY_REFUNDED, $sale->getState());
        }

        $refund = $transaction->getRelatedResources()[1]->getRefund();
        static::assertNotNull($refund);
        if ($refund !== null) {
            static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $refund->getState());
        }
    }

    public function testGetOrder(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::ORDER_PAYMENT_ID, $context);

        static::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);
        $order = $transaction->getRelatedResources()[0]->getOrder();

        static::assertNotNull($order);
        if ($order !== null) {
            static::assertSame(PaymentStatus::PAYMENT_PENDING, $order->getState());
        }
    }

    public function testGetCapturedAuthorizeWithRefunds(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::AUTHORIZE_PAYMENT_ID, $context);

        static::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);
        $authorization = $transaction->getRelatedResources()[0]->getAuthorization();

        static::assertNotNull($authorization);
        if ($authorization !== null) {
            static::assertSame(PaymentStatus::PAYMENT_CAPTURED, $authorization->getState());
        }

        $capture = $authorization = $transaction->getRelatedResources()[1]->getCapture();
        static::assertNotNull($capture);
        if ($capture !== null) {
            static::assertSame(PaymentStatus::PAYMENT_PARTIALLY_REFUNDED, $capture->getState());
        }

        $refund = $authorization = $transaction->getRelatedResources()[2]->getRefund();
        static::assertNotNull($refund);
        if ($refund !== null) {
            static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $refund->getState());
        }
    }

    public function testGetCapturedOrder(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::CAPTURED_ORDER_PAYMENT_ID, $context);

        static::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        static::assertInstanceOf(Payment\Transaction::class, $transaction);
        static::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);

        $order = $transaction->getRelatedResources()[0]->getOrder();
        static::assertNotNull($order);
        if ($order !== null) {
            static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $order->getState());
        }

        $capture = $transaction->getRelatedResources()[1]->getCapture();
        static::assertNotNull($capture);
        if ($capture !== null) {
            static::assertSame(PaymentStatus::PAYMENT_COMPLETED, $capture->getState());
        }
    }
}
