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
use Swag\PayPal\OrdersApi\Administration\PayPalOrdersController;
use Swag\PayPal\PayPal\ApiV2\Resource\AuthorizationResource;
use Swag\PayPal\PayPal\ApiV2\Resource\CaptureResource;
use Swag\PayPal\PayPal\ApiV2\Resource\OrderResource;
use Swag\PayPal\PayPal\ApiV2\Resource\RefundResource;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PayPalOrdersControllerTest extends TestCase
{
    use ServicesTrait;

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
        $orderDetailArray = \json_decode($content, true);
        static::assertSame(GetAuthorization::ID, $orderDetailArray['id']);
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
        $orderDetailArray = \json_decode($content, true);
        static::assertSame(GetCapture::ID, $orderDetailArray['id']);
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
        $orderDetailArray = \json_decode($content, true);
        static::assertSame(GetRefund::ID, $orderDetailArray['id']);
    }

    public function testRefundCapture(): void
    {
        $request = new Request([], [
            PayPalOrdersController::REQUEST_PARAMETER_AMOUNT => 12.34,
            PayPalOrdersController::REQUEST_PARAMETER_CURRENCY => 'EUR',
            PayPalOrdersController::REQUEST_PARAMETER_INVOICE_NUMBER => 'testInvoiceNumber',
            PayPalOrdersController::REQUEST_PARAMETER_NOTE_TO_PAYER => 'testNoteToPayer',
        ]);
        $response = $this->createController()->refundCapture(
            'orderTransactionId',
            'captureId',
            'paypalOrderId',
            Context::createDefaultContext(),
            $request
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $orderDetailArray = \json_decode($content, true);
        static::assertSame(
            RefundCapture::TOTAL_REFUNDED_AMOUNT_VALUE,
            $orderDetailArray['seller_payable_breakdown']['total_refunded_amount']['value']
        );
    }

    public function testCaptureAuthorization(): void
    {
        $request = new Request([], [
            PayPalOrdersController::REQUEST_PARAMETER_AMOUNT => 12.34,
            PayPalOrdersController::REQUEST_PARAMETER_CURRENCY => 'EUR',
            PayPalOrdersController::REQUEST_PARAMETER_INVOICE_NUMBER => 'testInvoiceNumber',
            PayPalOrdersController::REQUEST_PARAMETER_NOTE_TO_PAYER => 'testNoteToPayer',
        ]);
        $response = $this->createController()->captureAuthorization(
            'orderTransactionId',
            'authorizationId',
            Context::createDefaultContext(),
            $request
        );

        $content = $response->getContent();
        static::assertNotFalse($content);
        $orderDetailArray = \json_decode($content, true);
        static::assertSame(
            CaptureAuthorization::ID,
            $orderDetailArray['id']
        );
    }

    public function testVoidAuthorization(): void
    {
        $response = $this->createController()->voidAuthorization(
            'orderTransactionId',
            'authorizationId',
            Context::createDefaultContext()
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
            new PriceFormatter()
        );
    }
}
