<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Method;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Payment\Method\APMHandler;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\OrdersApi\Builder\APM\AbstractAPMOrderBuilder;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\PaymentStatusV2;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\SwagPayPal;
use Swag\PayPal\Test\Mock\PayPal\Client\_fixtures\V2\CaptureOrderAPM;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 *
 * @covers \Swag\PayPal\Checkout\Payment\Method\APMHandler
 */
#[Package('checkout')]
class APMHandlerFinalizeTest extends TestCase
{
    private APMHandler $handler;

    private OrderTransactionStateHandler&MockObject $orderTransactionStateHandler;

    private OrderResource&MockObject $orderResource;

    private TransactionDataService&MockObject $transactionDataService;

    protected function setUp(): void
    {
        $this->handler = new APMHandler(
            $this->transactionDataService = $this->createMock(TransactionDataService::class),
            $this->orderTransactionStateHandler = $this->createMock(OrderTransactionStateHandler::class),
            $this->createMock(SettingsValidationServiceInterface::class),
            $this->orderResource = $this->createMock(OrderResource::class),
            new NullLogger(),
            $this->createMock(AbstractAPMOrderBuilder::class)
        );
    }

    public function testFinalizeWithCompletedCaptureSetsTransactionPaid(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $paypalOrder = $this->createPayPalOrder(PaymentStatusV2::ORDER_CAPTURE_COMPLETED);

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->with(CaptureOrderAPM::ID, $salesChannelContext->getSalesChannelId())
            ->willReturn($paypalOrder);

        $this->orderTransactionStateHandler
            ->expects(static::once())
            ->method('paid')
            ->with($paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($paypalOrder, $paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->handler->finalize($paymentTransaction, new Request(), $salesChannelContext);
    }

    public function testFinalizeWithPendingCaptureKeepsTransactionState(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $paypalOrder = $this->createPayPalOrder(PaymentStatusV2::ORDER_CAPTURE_PENDING);

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($paypalOrder);

        // the webhook or the transaction status sync sets the state, once PayPal completes the capture
        $this->orderTransactionStateHandler
            ->expects(static::never())
            ->method(static::anything());

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($paypalOrder, $paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->handler->finalize($paymentTransaction, new Request(), $salesChannelContext);
    }

    /**
     * @dataProvider dataProviderFailedCaptureStatus
     */
    public function testFinalizeWithFailedCaptureFails(string $captureStatus): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($this->createPayPalOrder($captureStatus));

        $this->orderTransactionStateHandler
            ->expects(static::never())
            ->method(static::anything());

        $this->transactionDataService
            ->expects(static::never())
            ->method('setResourceId');

        $this->expectException(AsyncPaymentProcessException::class);
        $this->expectExceptionMessage(\sprintf('The asynchronous payment process was interrupted due to the following error:
Order "%s" failed', CaptureOrderAPM::ID));
        $this->handler->finalize($this->createPaymentTransactionStruct(), new Request(), $salesChannelContext);
    }

    public static function dataProviderFailedCaptureStatus(): \Generator
    {
        yield 'declined' => [PaymentStatusV2::ORDER_CAPTURE_DECLINED];
        yield 'failed' => [PaymentStatusV2::ORDER_CAPTURE_FAILED];
    }

    private function createPayPalOrder(string $captureStatus): Order
    {
        $paypalOrder = CaptureOrderAPM::get();
        $paypalOrder['purchase_units'][0]['payments']['captures'][0]['status'] = $captureStatus;

        return (new Order())->assign($paypalOrder);
    }

    private function createPaymentTransactionStruct(): AsyncPaymentTransactionStruct
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');
        $transaction->setCustomFields([
            SwagPayPal::ORDER_TRANSACTION_CUSTOM_FIELDS_PAYPAL_ORDER_ID => CaptureOrderAPM::ID,
        ]);

        return new AsyncPaymentTransactionStruct(
            $transaction,
            new OrderEntity(),
            'returnUrl'
        );
    }
}
