<?php declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPayPal\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use SwagPayPal\Controller\PayPalPaymentController;
use SwagPayPal\PayPal\Api\Payment\Transaction\RelatedResource;
use SwagPayPal\PayPal\Exception\RequiredParameterInvalidException;
use SwagPayPal\PayPal\Resource\AuthorizationResource;
use SwagPayPal\PayPal\Resource\CaptureResource;
use SwagPayPal\PayPal\Resource\OrdersResource;
use SwagPayPal\PayPal\Resource\SaleResource;
use SwagPayPal\Test\Helper\ServicesTrait;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\GetSaleResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\RefundCaptureResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\RefundSaleResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\VoidAuthorizationResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Client\_fixtures\VoidOrderResponseFixture;
use SwagPayPal\Test\Mock\PayPal\Resource\SaleResourceMock;
use Symfony\Component\HttpFoundation\Request;

class PayPalPaymentControllerTest extends TestCase
{
    use ServicesTrait;
    private const TEST_REFUND_INVOICE_NUMBER = 'testRefundInvoiceNumber';
    private const TEST_REFUND_AMOUNT = 5.5;
    private const TEST_REFUND_CURRENCY = 'EUR';

    public function testGetPaymentDetails(): void
    {
        $context = Context::createDefaultContext();
        $response = $this->createPaymentController()->paymentDetails($context, 'testPaymentId');

        $paymentDetails = json_decode($response->getContent(), true);

        static::assertSame(
            GetSaleResponseFixture::TRANSACTION_AMOUNT_DETAILS_SUBTOTAL,
            $paymentDetails['transactions'][0]['amount']['details']['subtotal']
        );
    }

    public function testRefundPayment(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();
        $response = $this->createPaymentController()->refundPayment(
            $request,
            $context,
            RelatedResource::SALE,
            'testPaymentId'
        );

        $refund = json_decode($response->getContent(), true);

        static::assertSame(RefundSaleResponseFixture::REFUND_AMOUNT, $refund['amount']['total']);
    }

    public function testRefundCapture(): void
    {
        $response = $this->createPaymentController()->refundPayment(
            new Request(),
            Context::createDefaultContext(),
            RelatedResource::CAPTURE,
            'testPaymentId'
        );

        $refund = json_decode($response->getContent(), true);

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
        $response = $this->createPaymentControllerWithSaleResourceMock()->refundPayment(
            $request,
            $context,
            RelatedResource::SALE,
            'testPaymentId'
        );

        $refund = json_decode($response->getContent(), true);

        static::assertSame((string) self::TEST_REFUND_AMOUNT, $refund['amount']['total']);
        static::assertSame(self::TEST_REFUND_CURRENCY, $refund['amount']['currency']);
        static::assertSame(self::TEST_REFUND_INVOICE_NUMBER, $refund['invoice_number']);
    }

    public function testRefundPaymentWithInvalidResourceType(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->createPaymentControllerWithSaleResourceMock()->refundPayment($request, $context, 'foo', 'testPaymentId');
    }

    public function testCapturePaymentAuthorization(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $response = $this->createPaymentController()->capturePayment(
            $request,
            $context,
            RelatedResource::AUTHORIZE,
            'testPaymentId'
        );

        $capture = json_decode($response->getContent(), true);

        static::assertTrue($capture['is_final_capture']);
    }

    public function testCapturePaymentOrders(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $response = $this->createPaymentController()->capturePayment(
            $request,
            $context,
            RelatedResource::ORDER,
            'testPaymentId'
        );

        $capture = json_decode($response->getContent(), true);

        static::assertTrue($capture['is_final_capture']);
    }

    public function testCapturePaymentWithInvalidResourceType(): void
    {
        $request = new Request();
        $context = Context::createDefaultContext();

        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->createPaymentController()->capturePayment($request, $context, RelatedResource::SALE, 'testPaymentId');
    }

    public function testVoidPaymentOrders(): void
    {
        $context = Context::createDefaultContext();
        $response = $this->createPaymentController()->voidPayment($context, RelatedResource::ORDER, 'testResourceId');

        $void = json_decode($response->getContent(), true);

        static::assertSame(VoidOrderResponseFixture::VOID_ID, $void['id']);
    }

    public function testVoidPaymentAuthorize(): void
    {
        $context = Context::createDefaultContext();
        $response = $this->createPaymentController()->voidPayment($context, RelatedResource::AUTHORIZE, 'testResourceId');

        $void = json_decode($response->getContent(), true);

        static::assertSame(VoidAuthorizationResponseFixture::VOID_ID, $void['id']);
    }

    public function testVoidPaymentInvalidResourceType(): void
    {
        $context = Context::createDefaultContext();
        $this->expectException(RequiredParameterInvalidException::class);
        $this->expectExceptionMessage('Required parameter "resourceType" is missing or invalid');
        $this->createPaymentController()->voidPayment($context, RelatedResource::SALE, 'testResourceId');
    }

    private function createPaymentController(): PayPalPaymentController
    {
        return new PayPalPaymentController(
            $this->createPaymentResource(),
            new SaleResource($this->createPayPalClientFactory()),
            new AuthorizationResource($this->createPayPalClientFactory()),
            new OrdersResource($this->createPayPalClientFactory()),
            new CaptureResource($this->createPayPalClientFactory())
        );
    }

    private function createPaymentControllerWithSaleResourceMock(): PayPalPaymentController
    {
        return new PayPalPaymentController(
            $this->createPaymentResource(),
            new SaleResourceMock($this->createPayPalClientFactory()),
            new AuthorizationResource($this->createPayPalClientFactory()),
            new OrdersResource($this->createPayPalClientFactory()),
            new CaptureResource($this->createPayPalClientFactory())
        );
    }
}
