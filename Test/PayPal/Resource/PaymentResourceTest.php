<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\PayPal\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\PayPal\Api\Payment;
use SwagPayPal\PayPal\PaymentStatus;
use SwagPayPal\Test\Helper\ConstantsForTesting;
use SwagPayPal\Test\Helper\PaymentTransactionTrait;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\CreatePaymentResponseFixture;

class PaymentResourceTest extends TestCase
{
    use PaymentTransactionTrait,
        ServicesTrait;

    public const ORDER_PAYMENT_ID = 'testOrderPaymentId';

    private const TEST_PAYMENT_ID = 'testPaymentId';

    public function testCreate(): void
    {
        $context = Context::createDefaultContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $payment = $this->createPaymentBuilder()->getPayment($paymentTransaction, $context);
        $createdPayment = $this->createPaymentResource()->create($payment, $context);

        self::assertInstanceOf(Payment::class, $createdPayment);
        self::assertSame(CreatePaymentResponseFixture::CREATE_PAYMENT_ID, $createdPayment->getId());
        $transaction = $createdPayment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        $link = $createdPayment->getLinks()[1];
        self::assertInstanceOf(Payment\Link::class, $link);
        self::assertSame(CreatePaymentResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $link->getHref());
    }

    public function testExecuteSale(): void
    {
        $context = Context::createDefaultContext();
        $executedPayment = $this->createPaymentResource()->execute('testPayerId', self::TEST_PAYMENT_ID, $context);

        self::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        self::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);
        $sale = $transaction->getRelatedResources()[0]->getSale();
        if ($sale !== null) {
            self::assertSame(PaymentStatus::PAYMENT_COMPLETED, $sale->getState());
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

        self::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        self::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);
        $authorization = $transaction->getRelatedResources()[0]->getAuthorization();
        if ($authorization !== null) {
            self::assertSame(PaymentStatus::PAYMENT_AUTHORIZED, $authorization->getState());
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

        self::assertInstanceOf(Payment::class, $executedPayment);
        $transaction = $executedPayment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        self::assertInstanceOf(Payment\Link::class, $executedPayment->getLinks()[0]);
        $order = $transaction->getRelatedResources()[0]->getOrder();
        if ($order !== null) {
            self::assertSame(PaymentStatus::PAYMENT_PENDING, $order->getState());
        }
    }

    public function testGetSale(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::TEST_PAYMENT_ID, $context);

        self::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        self::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);
        $sale = $transaction->getRelatedResources()[0]->getSale();
        if ($sale !== null) {
            self::assertSame(PaymentStatus::PAYMENT_COMPLETED, $sale->getState());
        }
    }

    public function testGetOrder(): void
    {
        $context = Context::createDefaultContext();
        $payment = $this->createPaymentResource()->get(self::ORDER_PAYMENT_ID, $context);

        self::assertInstanceOf(Payment::class, $payment);
        $transaction = $payment->getTransactions()[0];
        self::assertInstanceOf(Payment\Transaction::class, $transaction);
        self::assertInstanceOf(Payment\Link::class, $payment->getLinks()[0]);
        $order = $transaction->getRelatedResources()[0]->getOrder();
        if ($order !== null) {
            self::assertSame(PaymentStatus::PAYMENT_PENDING, $order->getState());
        }
    }
}
