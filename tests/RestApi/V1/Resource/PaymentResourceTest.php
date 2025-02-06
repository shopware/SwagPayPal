<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\RestApi\V1\Resource;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Test\TestDefaults;
use Swag\PayPal\RestApi\V1\PaymentStatusV1;
use Swag\PayPal\RestApi\V1\Resource\PaymentResource;
use Swag\PayPal\Test\Helper\PaymentTransactionTrait;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\PayPalClientFactoryMock;

/**
 * @internal
 */
#[Package('checkout')]
class PaymentResourceTest extends TestCase
{
    use PaymentTransactionTrait;
    use ServicesTrait;

    public const CAPTURED_ORDER_PAYMENT_ID = 'testCapturedOrderPaymentId';
    public const ORDER_PAYMENT_ID = 'testOrderPaymentId';

    public const AUTHORIZE_PAYMENT_ID = 'testAuthorizePaymentId';

    public const SALE_WITH_REFUND_PAYMENT_ID = 'testSaleWithRefundPaymentId';
    private const TEST_PAYMENT_ID = 'testPaymentId';

    public function testGetSale(): void
    {
        $payment = $this->createPaymentResource()->get(self::TEST_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()->first();
        static::assertNotNull($payment->getLinks()->first());

        $sale = $transaction?->getRelatedResources()->first()?->getSale();
        static::assertNotNull($sale);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $sale->getState());
    }

    public function testGetSaleWithRefund(): void
    {
        $payment = $this->createPaymentResource()->get(self::SALE_WITH_REFUND_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()->first();
        static::assertNotNull($payment->getLinks()->first());

        $sale = $transaction?->getRelatedResources()->first()?->getSale();
        static::assertNotNull($sale);
        static::assertSame(PaymentStatusV1::PAYMENT_PARTIALLY_REFUNDED, $sale->getState());

        $refund = $transaction?->getRelatedResources()->getAt(1)?->getRefund();
        static::assertNotNull($refund);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $refund->getState());
    }

    public function testGetOrder(): void
    {
        $payment = $this->createPaymentResource()->get(self::ORDER_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()->first();
        static::assertNotNull($payment->getLinks()->first());
        $order = $transaction?->getRelatedResources()->first()?->getOrder();

        static::assertNotNull($order);
        static::assertSame(PaymentStatusV1::PAYMENT_PENDING, $order->getState());
    }

    public function testGetCapturedAuthorizeWithRefunds(): void
    {
        $payment = $this->createPaymentResource()->get(self::AUTHORIZE_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()->first();
        static::assertNotNull($payment->getLinks()->first());
        $authorization = $transaction?->getRelatedResources()->first()?->getAuthorization();

        static::assertNotNull($authorization);
        static::assertSame(PaymentStatusV1::PAYMENT_CAPTURED, $authorization->getState());

        $capture = $transaction?->getRelatedResources()->getAt(1)?->getCapture();
        static::assertNotNull($capture);
        static::assertSame(PaymentStatusV1::PAYMENT_PARTIALLY_REFUNDED, $capture->getState());

        $refund = $transaction?->getRelatedResources()->getAt(2)?->getRefund();
        static::assertNotNull($refund);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $refund->getState());
    }

    public function testGetCapturedOrder(): void
    {
        $payment = $this->createPaymentResource()->get(self::CAPTURED_ORDER_PAYMENT_ID, TestDefaults::SALES_CHANNEL);

        $transaction = $payment->getTransactions()->first();
        static::assertNotNull($payment->getLinks()->first());

        $order = $transaction?->getRelatedResources()->first()?->getOrder();
        static::assertNotNull($order);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $order->getState());

        $capture = $transaction?->getRelatedResources()->getAt(1)?->getCapture();
        static::assertNotNull($capture);
        static::assertSame(PaymentStatusV1::PAYMENT_COMPLETED, $capture->getState());
    }

    private function createPaymentResource(): PaymentResource
    {
        return new PaymentResource(new PayPalClientFactoryMock(new NullLogger()));
    }
}
