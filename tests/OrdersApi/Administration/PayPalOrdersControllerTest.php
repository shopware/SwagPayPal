<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\OrdersApi\Administration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Swag\PayPal\OrdersApi\Administration\Exception\RequestParameterInvalidException;
use Swag\PayPal\OrdersApi\Administration\PayPalOrdersController;
use Swag\PayPal\OrdersApi\Administration\Service\CaptureRefundCreator;
use Swag\PayPal\RestApi\V2\Resource\AuthorizationResource;
use Swag\PayPal\RestApi\V2\Resource\CaptureResource;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\RestApi\V2\Resource\RefundResource;
use Swag\PayPal\Test\Helper\ConstantsForTesting;
use Swag\PayPal\Test\Helper\ServicesTrait;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetAuthorization;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetRefund;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\RefundCapture;
use Swag\PayPal\Test\Mock\Repositories\OrderTransactionRepoMock;
use Swag\PayPal\Test\Mock\Util\PaymentStatusUtilV2Mock;
use Swag\PayPal\Util\PriceFormatter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PayPalOrdersControllerTest extends TestCase
{
    use ServicesTrait;

    private const AMOUNT = 12.34;
    private const INVOICE_NUMBER = 'testInvoiceNumber';
    private const TOO_LONG_INVOICE_NUMBER = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquy';
    private const NOTE_TO_PAYER = 'testNoteToPayer';
    private const TOO_LONG_NOTE_TO_PAYER = 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata test';

    public function testOrdersDetail(): void
    {
        $response = $this->createController()->orderDetails(
            'orderTransactionId',
            'paypalOrderId',
            Context::createDefaultContext()
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $orderDetailArray = \json_decode($content, true);
        static::assertSame(GetOrderCapture::ID, $orderDetailArray['id']);
    }

    public function testOrdersDetailThrowsExceptionWithoutTransaction(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(ConstantsForTesting::WITHOUT_TRANSACTION, new ArrayStruct());

        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id orderTransactionId is invalid or could not be found.');
        $this->createController()->orderDetails(
            'orderTransactionId',
            'paypalOrderId',
            $context
        );
    }

    public function testOrdersDetailThrowsExceptionWithoutOrder(): void
    {
        $context = Context::createDefaultContext();
        $context->addExtension(ConstantsForTesting::WITHOUT_ORDER, new ArrayStruct());

        $this->expectException(InvalidTransactionException::class);
        $this->expectExceptionMessage('The transaction with id orderTransactionId is invalid or could not be found.');
        $this->createController()->orderDetails(
            'orderTransactionId',
            'paypalOrderId',
            $context
        );
    }

    public function testAuthorizationsDetail(): void
    {
        $response = $this->createController()->authorizationDetails(
            'orderTransactionId',
            'authorizationId',
            Context::createDefaultContext()
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $authorizationDetailArray = \json_decode($content, true);
        static::assertSame(GetAuthorization::ID, $authorizationDetailArray['id']);
    }

    public function testCapturesDetail(): void
    {
        $response = $this->createController()->captureDetails(
            'orderTransactionId',
            'captureId',
            Context::createDefaultContext()
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $captureDetailArray = \json_decode($content, true);
        static::assertSame(GetCapture::ID, $captureDetailArray['id']);
    }

    public function testRefundsDetail(): void
    {
        $response = $this->createController()->refundDetails(
            'orderTransactionId',
            'refundId',
            Context::createDefaultContext()
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $refundDetailArray = \json_decode($content, true);
        static::assertSame(GetRefund::ID, $refundDetailArray['id']);
    }

    public function testRefundCapture(): void
    {
        $request = $this->createCaptureRefundRequest();
        $content = $this->executeRefund($request)->getContent();

        static::assertNotFalse($content);
        $refundDetailArray = \json_decode($content, true);
        static::assertSame(
            (string) self::AMOUNT,
            $refundDetailArray['seller_payable_breakdown']['total_refunded_amount']['value']
        );
        static::assertSame(self::INVOICE_NUMBER, $refundDetailArray['invoice_id']);
        static::assertSame(self::NOTE_TO_PAYER, $refundDetailArray['note_to_payer']);
    }

    public function testRefundCaptureWithNoAmount(): void
    {
        $request = $this->createCaptureRefundRequest(0);
        $content = $this->executeRefund($request)->getContent();

        static::assertNotFalse($content);
        $refundDetailArray = \json_decode($content, true);
        static::assertSame(
            RefundCapture::TOTAL_REFUNDED_AMOUNT_VALUE,
            $refundDetailArray['seller_payable_breakdown']['total_refunded_amount']['value']
        );
    }

    public function testRefundCaptureWithNoInvoiceId(): void
    {
        $request = $this->createCaptureRefundRequest(self::AMOUNT, '');
        $content = $this->executeRefund($request)->getContent();

        static::assertNotFalse($content);
        $refundDetailArray = \json_decode($content, true);
        static::assertNull($refundDetailArray['invoice_id']);
    }

    public function testRefundCaptureWithTooLongInvoiceId(): void
    {
        $request = $this->createCaptureRefundRequest(self::AMOUNT, self::TOO_LONG_INVOICE_NUMBER);
        $this->expectException(RequestParameterInvalidException::class);
        $this->expectExceptionMessage('Parameter "invoiceNumber" is invalid.
Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund::$invoiceId must not be longer than 127 characters');
        $this->executeRefund($request)->getContent();
    }

    public function testRefundCaptureWithNoNoteToPayer(): void
    {
        $request = $this->createCaptureRefundRequest(self::AMOUNT, self::INVOICE_NUMBER, '');
        $content = $this->executeRefund($request)->getContent();

        static::assertNotFalse($content);
        $refundDetailArray = \json_decode($content, true);
        static::assertNull($refundDetailArray['note_to_payer']);
    }

    public function testRefundCaptureWithTooLongNoteToPayer(): void
    {
        $request = $this->createCaptureRefundRequest(self::AMOUNT, '', self::TOO_LONG_NOTE_TO_PAYER);
        $this->expectException(RequestParameterInvalidException::class);
        $this->expectExceptionMessage('Parameter "noteToPayer" is invalid.
Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund::$invoiceId must not be longer than 255 characters');
        $this->executeRefund($request)->getContent();
    }

    public function testCaptureAuthorization(): void
    {
        $request = $this->createCaptureRefundRequest();
        $request->request->set(PayPalOrdersController::REQUEST_PARAMETER_IS_FINAL, 'false');
        $response = $this->createController()->captureAuthorization(
            'orderTransactionId',
            'authorizationId',
            Context::createDefaultContext(),
            $request
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $captureDetailArray = \json_decode($content, true);
        static::assertSame(
            CaptureAuthorization::ID,
            $captureDetailArray['id']
        );
        static::assertFalse($captureDetailArray['final_capture']);
    }

    public function testVoidAuthorization(): void
    {
        $response = $this->createController()->voidAuthorization(
            'orderTransactionId',
            'authorizationId',
            Context::createDefaultContext(),
            new Request()
        );

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    private function createController(): PayPalOrdersController
    {
        $clientFactory = $this->createPayPalClientFactory();
        $orderTransactionRepo = new OrderTransactionRepoMock();

        return new PayPalOrdersController(
            new OrderResource($clientFactory),
            new AuthorizationResource($clientFactory),
            new CaptureResource($clientFactory),
            new RefundResource($clientFactory),
            $orderTransactionRepo,
            new PaymentStatusUtilV2Mock(),
            new CaptureRefundCreator(
                new PriceFormatter()
            )
        );
    }

    private function createCaptureRefundRequest(
        float $amount = self::AMOUNT,
        string $invoiceNumber = self::INVOICE_NUMBER,
        string $noteToPayer = self::NOTE_TO_PAYER
    ): Request {
        return new Request([], [
            PayPalOrdersController::REQUEST_PARAMETER_AMOUNT => $amount,
            PayPalOrdersController::REQUEST_PARAMETER_CURRENCY => 'EUR',
            PayPalOrdersController::REQUEST_PARAMETER_INVOICE_NUMBER => $invoiceNumber,
            PayPalOrdersController::REQUEST_PARAMETER_NOTE_TO_PAYER => $noteToPayer,
        ]);
    }

    private function executeRefund(Request $request): JsonResponse
    {
        return $this->createController()->refundCapture(
            'orderTransactionId',
            'captureId',
            'paypalOrderId',
            Context::createDefaultContext(),
            $request
        );
    }
}
