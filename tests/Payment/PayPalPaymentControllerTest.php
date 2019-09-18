<?php declare(strict_types=1);

namespace Swag\PayPal\Test\Payment;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Swag\PayPal\Payment\Exception\RequiredParameterInvalidException;
use Swag\PayPal\Payment\PayPalPaymentController;
use Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;
use Swag\PayPal\PayPal\Resource\AuthorizationResource;
use Swag\PayPal\PayPal\Resource\CaptureResource;
use Swag\PayPal\PayPal\Resource\OrdersResource;
use Swag\PayPal\PayPal\Resource\SaleResource;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\GetSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\RefundCaptureResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\RefundSaleResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidAuthorizationResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\VoidOrderResponseFixture;
use Swag\PayPal\Test\Mock\PayPal\Resource\SaleResourceMock;
use Swag\PayPal\Test\Mock\Repositories\OrderRepositoryMock;
use Swag\PayPal\Test\Mock\Util\PaymentStatusUtilMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentControllerTest extends TestCase
{
    use ServicesTrait;

    private const TEST_REFUND_INVOICE_NUMBER = 'testRefundInvoiceNumber';
    private const TEST_REFUND_AMOUNT = 5.5;
    private const TEST_REFUND_CURRENCY = 'EUR';
    private const TEST_REFUND_DESCRIPTION = 'testDescription';
    private const TEST_REFUND_REASON = 'testReason';
    private const ORDER_ID = '98432def39fc4624b33213a56b8c944d';

    public function testGetPaymentDetails(): void
    {
        $context = Context::createDefaultContext();
        $responseContent = $this->createPaymentController()->paymentDetails(self::ORDER_ID, 'testPaymentId', $context)->getContent();
        static::assertNotFalse($responseContent);

        $paymentDetails = json_decode($responseContent, true);

        static::assertSame(
            GetSaleResponseFixture::TRANSACTION_AMOUNT_DETAILS_SUBTOTAL,
            $paymentDetails['transactions'][0]['amount']['details']['subtotal']
        );
    }

    public function testRefundPayment(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();
        $responseContent = $this->createPaymentController()->refundPayment(
            $request,
            $context,
            RelatedResource::SALE,
            'testPaymentId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $refund = json_decode($responseContent, true);

        static::assertSame(RefundSaleResponseFixture::REFUND_AMOUNT, $refund['amount']['total']);
    }

    public function testRefundCapture(): void
    {
        $responseContent = $this->createPaymentController()->refundPayment(
            new Request(),
            Context::createDefaultContext(),
            RelatedResource::CAPTURE,
            'testPaymentId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $refund = json_decode($responseContent, true);

        static::assertSame(RefundCaptureResponseFixture::REFUND_AMOUNT, $refund['amount']['total']);
    }

    public function testRefundPaymentWithInvoiceAndAmount(): void
    {
        $request = new Request([], [
            PayPalPaymentController::REQUEST_PARAMETER_REFUND_INVOICE_NUMBER => self::TEST_REFUND_INVOICE_NUMBER,
            PayPalPaymentController::REQUEST_PARAMETER_REFUND_AMOUNT => self::TEST_REFUND_AMOUNT,
            PayPalPaymentController::REQUEST_PARAMETER_CURRENCY => self::TEST_REFUND_CURRENCY,
        ]);
        $context = Context::createDefaultContext();
        $responseContent = $this->createPaymentControllerWithSaleResourceMock()->refundPayment(
            $request,
            $context,
            RelatedResource::SALE,
            'testPaymentId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $refund = json_decode($responseContent, true);

        static::assertSame((string) self::TEST_REFUND_AMOUNT, $refund['amount']['total']);
        static::assertSame(self::TEST_REFUND_CURRENCY, $refund['amount']['currency']);
        static::assertSame(self::TEST_REFUND_INVOICE_NUMBER, $refund['invoice_number']);
    }

    public function testRefundPaymentWithReasonAndDescription(): void
    {
        $request = new Request([], [
            PayPalPaymentController::REQUEST_PARAMETER_REFUND_INVOICE_NUMBER => self::TEST_REFUND_INVOICE_NUMBER,
            PayPalPaymentController::REQUEST_PARAMETER_REFUND_AMOUNT => self::TEST_REFUND_AMOUNT,
            PayPalPaymentController::REQUEST_PARAMETER_CURRENCY => self::TEST_REFUND_CURRENCY,
            PayPalPaymentController::REQUEST_PARAMETER_DESCRIPTION => self::TEST_REFUND_DESCRIPTION,
            PayPalPaymentController::REQUEST_PARAMETER_REASON => self::TEST_REFUND_REASON,
        ]);
        $context = Context::createDefaultContext();
        $responseContent = $this->createPaymentControllerWithSaleResourceMock()->refundPayment(
          $request,
          $context,
          RelatedResource::SALE,
          'testPaymentId',
          'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $refund = json_decode($responseContent, true);

        static::assertSame((string) self::TEST_REFUND_AMOUNT, $refund['amount']['total']);
        static::assertSame(self::TEST_REFUND_CURRENCY, $refund['amount']['currency']);
        static::assertSame(self::TEST_REFUND_INVOICE_NUMBER, $refund['invoice_number']);
        static::assertSame(self::TEST_REFUND_REASON, $refund['reason']);
        static::assertSame(self::TEST_REFUND_DESCRIPTION, $refund['description']);
    }

    public function testRefundPaymentWithInvalidResourceType(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->createPaymentControllerWithSaleResourceMock()->refundPayment(
            $request,
            $context,
            'foo',
            'testPaymentId',
            'testOrderId'
        );
    }

    public function testCapturePaymentAuthorization(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $responseContent = $this->createPaymentController()->capturePayment(
            $request,
            $context,
            RelatedResource::AUTHORIZE,
            'testPaymentId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $capture = json_decode($responseContent, true);

        static::assertTrue($capture['is_final_capture']);
    }

    public function testCapturePaymentOrders(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $responseContent = $this->createPaymentController()->capturePayment(
            $request,
            $context,
            RelatedResource::ORDER,
            'testPaymentId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $capture = json_decode($responseContent, true);

        static::assertTrue($capture['is_final_capture']);
    }

    public function testCapturePaymentWithInvalidResourceType(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->createPaymentController()->capturePayment(
            $request,
            $context,
            RelatedResource::SALE,
            'testPaymentId',
            'testOrderId'
        );
    }

    public function testVoidPaymentOrders(): void
    {
        $context = Context::createDefaultContext();
        $responseContent = $this->createPaymentController()->voidPayment(
            $context,
            RelatedResource::ORDER,
            'testResourceId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $void = json_decode($responseContent, true);

        static::assertSame(VoidOrderResponseFixture::VOID_ID, $void['id']);
    }

    public function testVoidPaymentAuthorize(): void
    {
        $context = Context::createDefaultContext();
        $responseContent = $this->createPaymentController()->voidPayment(
            $context,
            RelatedResource::AUTHORIZE,
            'testResourceId',
            'testOrderId'
        )->getContent();
        static::assertNotFalse($responseContent);

        $void = json_decode($responseContent, true);

        static::assertSame(VoidAuthorizationResponseFixture::VOID_ID, $void['id']);
    }

    public function testVoidPaymentInvalidResourceType(): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->createPaymentController()->voidPayment(
            $context,
            RelatedResource::SALE,
            'testResourceId',
            'testOrderId'
        );
    }

    private function createPaymentController(): PayPalPaymentController
    {
        return new PayPalPaymentController(
            $this->createPaymentResource(),
            new SaleResource($this->createPayPalClientFactory()),
            new AuthorizationResource($this->createPayPalClientFactory()),
            new OrdersResource($this->createPayPalClientFactory()),
            new CaptureResource($this->createPayPalClientFactory()),
            new PaymentStatusUtilMock(),
            new OrderRepositoryMock()
        );
    }

    private function createPaymentControllerWithSaleResourceMock(): PayPalPaymentController
    {
        return new PayPalPaymentController(
            $this->createPaymentResource(),
            new SaleResourceMock($this->createPayPalClientFactory()),
            new AuthorizationResource($this->createPayPalClientFactory()),
            new OrdersResource($this->createPayPalClientFactory()),
            new CaptureResource($this->createPayPalClientFactory()),
            new PaymentStatusUtilMock(),
            new OrderRepositoryMock()
        );
    }
}
