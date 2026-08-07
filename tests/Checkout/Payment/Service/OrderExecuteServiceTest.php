<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Shopware\PayPalSDK\Struct\V2\Order;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\OrdersApi\Patch\OrderNumberPatchBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CreateOrderCapture;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\GetCapturedOrderCapture;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(OrderExecuteService::class)]
class OrderExecuteServiceTest extends TestCase
{
    public function testOrderGetOnMissingPayments(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = new OrderExecuteService(
            $orderResource,
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(OrderNumberPatchBuilder::class),
            new NullLogger(),
        );

        $captureDataWithMissingPayment = CaptureOrderCapture::get();
        $captureDataWithMissingPayment['purchase_units'][0]['payments'] = null;

        $orderResource->expects($this->once())
            ->method('capture')
            ->willReturn((new Order())->assign($captureDataWithMissingPayment));

        $orderResource->expects($this->once())
            ->method('get')
            ->willReturn((new Order())->assign(GetCapturedOrderCapture::get()));

        $orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testCapturePayerActionRequiredIsConverted(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = $this->createOrderExecuteService($orderResource);

        $orderResource->expects($this->once())
            ->method('capture')
            ->willThrowException($this->createPayPalApiException(PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED));

        $this->expectExceptionObject(PayerActionRequiredException::payerActionRequired(CreateOrderCapture::ID));

        $orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign(CreateOrderCapture::get()),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    /**
     * The PayPal debug id only exists on the rejected response, so losing it leaves the payment log unattributable.
     */
    public function testCapturePayerActionRequiredKeepsTheRejectionAsPreviousException(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $rejection = $this->createPayPalApiException(PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED);

        $orderResource->expects($this->once())
            ->method('capture')
            ->willThrowException($rejection);

        try {
            $this->createOrderExecuteService($orderResource)->captureOrAuthorizeOrder(
                Uuid::randomHex(),
                (new Order())->assign(CreateOrderCapture::get()),
                Uuid::randomHex(),
                Context::createDefaultContext(),
                Uuid::randomHex(),
            );
        } catch (PayerActionRequiredException $e) {
            static::assertSame($rejection, $e->getPrevious());

            return;
        }

        static::fail('Expected a PayerActionRequiredException');
    }

    public function testPayerActionRequiredOrderStatusSkipsCapture(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = $this->createOrderExecuteService($orderResource);

        $orderData = CreateOrderCapture::get();
        $orderData['status'] = ConstantsV2::ORDER_PAYER_ACTION_REQUIRED;

        $orderResource->expects($this->never())->method('capture');
        $orderResource->expects($this->never())->method('authorize');

        $this->expectException(PayerActionRequiredException::class);

        $orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testCaptureWithoutStatusStillCaptures(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = $this->createOrderExecuteService($orderResource);

        $orderData = CreateOrderCapture::get();
        unset($orderData['status']);

        $orderResource->expects($this->once())
            ->method('capture')
            ->willReturn((new Order())->assign(CaptureOrderCapture::get()));

        $orderExecuteService->captureOrAuthorizeOrder(
            Uuid::randomHex(),
            (new Order())->assign($orderData),
            Uuid::randomHex(),
            Context::createDefaultContext(),
            Uuid::randomHex(),
        );
    }

    public function testUnrelatedUnprocessableEntityIsNotConverted(): void
    {
        $orderResource = $this->createMock(OrderResource::class);
        $orderExecuteService = $this->createOrderExecuteService($orderResource);

        $orderResource->expects($this->once())
            ->method('capture')
            ->willThrowException($this->createPayPalApiException('INSTRUMENT_DECLINED'));

        try {
            $orderExecuteService->captureOrAuthorizeOrder(
                Uuid::randomHex(),
                (new Order())->assign(CreateOrderCapture::get()),
                Uuid::randomHex(),
                Context::createDefaultContext(),
                Uuid::randomHex(),
            );
            static::fail('Expected a PayPalApiException to be thrown');
        } catch (PayPalApiException $e) {
            static::assertNotInstanceOf(PayerActionRequiredException::class, $e);
            static::assertSame('INSTRUMENT_DECLINED', $e->getIssue());
        }
    }

    private function createOrderExecuteService(OrderResource $orderResource): OrderExecuteService
    {
        return new OrderExecuteService(
            $orderResource,
            $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(OrderNumberPatchBuilder::class),
            new NullLogger(),
        );
    }

    private function createPayPalApiException(string $issue): PayPalApiException
    {
        return new PayPalApiException(
            ApiException::CODE_UNPROCESSABLE_ENTITY,
            'The requested action could not be performed, semantically incorrect, or failed business validation.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            $issue,
        );
    }
}
