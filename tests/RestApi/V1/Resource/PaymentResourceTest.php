<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V1\Api\Payment;
use Swag\PayPal\RestApi\V1\Api\Payment\Link;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V1\CreateResponseFixture;

/**
 * @internal
 */
class PaymentResourceTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;

    public const CAPTURED_ORDER_PAYMENT_ID = 'testCapturedOrderPaymentId';
    public const ORDER_PAYMENT_ID = 'testOrderPaymentId';

    public const AUTHORIZE_PAYMENT_ID = 'testAuthorizePaymentId';

    public const SALE_WITH_REFUND_PAYMENT_ID = 'testSaleWithRefundPaymentId';
    private const TEST_PAYMENT_ID = 'testPaymentId';

    public function testCreate(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $payment = $this->createPaymentBuilder($this->createDefaultSystemConfig())->getPayment($paymentTransaction, $salesChannelContext);
        $createdPayment = $this->createPaymentResource($this->createDefaultSystemConfig())->create(
            $payment,
            $salesChannelContext->getSalesChannel()->getId(),
            PartnerAttributionId::PAYPAL_CLASSIC
        );

        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_ID, $createdPayment->getId());
        $link = $createdPayment->getLinks()[1];
        static::assertSame(CreateResponseFixture::CREATE_PAYMENT_APPROVAL_URL, $link->getHref());
    }

    public function testExecuteSale(): void
    {
        $executedPayment = $this->createPaymentResource($this->createDefaultSystemConfig())->execute(
            'testPayerId',
            self::TEST_PAYMENT_ID,
            TestDefaults::SALES_CHANNEL
        );

        $transaction = $executedPayment->getTransactions()[0];
        static::assertArrayHasKey(0, $executedPayment->getLinks());

        $sale = $transaction->getRelatedResources()[0]->getSale();
        static::assertNotNull($sale);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $sale->getState());
    }

    public function testExecuteAuthorize(): void
    {
        $executedPayment = $this->createPaymentResource($this->createDefaultSystemConfig())->execute(
            ConstantsForTesting::PAYER_ID_PAYMENT_AUTHORIZE,
            self::TEST_PAYMENT_ID,
            TestDefaults::SALES_CHANNEL
        );

        $transaction = $executedPayment->getTransactions()[0];
        static::assertArrayHasKey(0, $executedPayment->getLinks());

        $authorization = $transaction->getRelatedResources()[0]->getAuthorization();
        static::assertNotNull($authorization);
        static::assertSame(PaymentStatusV1::PAYMENT_AUTHORIZED, $authorization->getState());
    }

    public function testExecuteOrder(): void
    {
        $executedPayment = $this->createPaymentResource($this->createDefaultSystemConfig())->execute(
            ConstantsForTesting::PAYER_ID_PAYMENT_ORDER,
            self::TEST_PAYMENT_ID,
            TestDefaults::SALES_CHANNEL
        );

        $transaction = $executedPayment->getTransactions()[0];
        static::assertArrayHasKey(0, $executedPayment->getLinks());

        $order = $transaction->getRelatedResources()[0]->getOrder();
        static::assertNotNull($order);
        static::assertSame(PaymentStatusV1::PAYMENT_PENDING, $order->getState());
    }

    public function testGetSale(): void
    {
        $payment = $this->createPaymentResource($this->createDefaultSystemConfig())->get(self::TEST_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()[0];
        static::assertArrayHasKey(0, $payment->getLinks());

        $sale = $transaction->getRelatedResources()[0]->getSale();
        static::assertNotNull($sale);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $sale->getState());
    }

    public function testGetSaleWithRefund(): void
    {
        $payment = $this->createPaymentResource($this->createDefaultSystemConfig())->get(self::SALE_WITH_REFUND_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()[0];
        static::assertArrayHasKey(0, $payment->getLinks());

        $sale = $transaction->getRelatedResources()[0]->getSale();
        static::assertNotNull($sale);
        static::assertSame(PaymentStatusV1::PAYMENT_PARTIALLY_REFUNDED, $sale->getState());

        $refund = $transaction->getRelatedResources()[1]->getRefund();
        static::assertNotNull($refund);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $refund->getState());
    }

    public function testGetOrder(): void
    {
        $payment = $this->createPaymentResource($this->createDefaultSystemConfig())->get(self::ORDER_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()[0];
        static::assertArrayHasKey(0, $payment->getLinks());
        $order = $transaction->getRelatedResources()[0]->getOrder();

        static::assertNotNull($order);
        static::assertSame(PaymentStatusV1::PAYMENT_PENDING, $order->getState());
    }

    public function testGetCapturedAuthorizeWithRefunds(): void
    {
        $payment = $this->createPaymentResource($this->createDefaultSystemConfig())->get(self::AUTHORIZE_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()[0];
        static::assertArrayHasKey(0, $payment->getLinks());
        $authorization = $transaction->getRelatedResources()[0]->getAuthorization();

        static::assertNotNull($authorization);
        static::assertSame(PaymentStatusV1::PAYMENT_CAPTURED, $authorization->getState());

        $capture = $transaction->getRelatedResources()[1]->getCapture();
        static::assertNotNull($capture);
        static::assertSame(PaymentStatusV1::PAYMENT_PARTIALLY_REFUNDED, $capture->getState());

        $refund = $transaction->getRelatedResources()[2]->getRefund();
        static::assertNotNull($refund);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $refund->getState());
    }

    public function testGetCapturedOrder(): void
    {
        $payment = $this->createPaymentResource($this->createDefaultSystemConfig())->get(self::CAPTURED_ORDER_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()[0];
        static::assertArrayHasKey(0, $payment->getLinks());

        $order = $transaction->getRelatedResources()[0]->getOrder();
        static::assertNotNull($order);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $order->getState());

        $capture = $transaction->getRelatedResources()[1]->getCapture();
        static::assertNotNull($capture);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $capture->getState());
    }
}
