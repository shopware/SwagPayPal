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
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Checkout\Test\Cart\Common\Generator;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\PayPal\Checkout\ACDC\ACDCValidatorInterface;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\ACDCHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\RestApi\PartnerAttributionId;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Card\AuthenticationResult;
use Swag\PayPal\RestApi\V2\Api\Order\PaymentSource\Paypal;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Exception\PayPalSettingsInvalidException;
use Swag\PayPal\Setting\Service\SettingsValidationService;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
#[Package('checkout')]
class ACDCHandlerTest extends TestCase
{
    private ACDCHandler $handler;

    private OrderExecuteService&MockObject $orderExecuteService;

    private OrderPatchService&MockObject $orderPatchService;

    private TransactionDataService&MockObject $transactionDataService;

    private OrderTransactionStateHandler&MockObject $orderTransactionStateHandler;

    private SettingsValidationService&MockObject $settingsValidationService;

    private ACDCValidatorInterface&MockObject $acdcValidator;

    private OrderResource&MockObject $orderResource;

    protected function setUp(): void
    {
        $this->handler = new ACDCHandler(
            $this->settingsValidationService = $this->createMock(SettingsValidationService::class),
            $this->orderTransactionStateHandler = $this->createMock(OrderTransactionStateHandler::class),
            $this->orderExecuteService = $this->createMock(OrderExecuteService::class),
            $this->orderPatchService = $this->createMock(OrderPatchService::class),
            $this->transactionDataService = $this->createMock(TransactionDataService::class),
            new NullLogger(),
            $this->orderResource = $this->createMock(OrderResource::class),
            $this->acdcValidator = $this->createMock(ACDCValidatorInterface::class),
        );
    }

    public function testPay(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();

        $this->transactionDataService
            ->expects(static::once())
            ->method('setOrderId')
            ->with(
                $paymentTransaction->getOrderTransaction()->getId(),
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP,
                $salesChannelContext
            );

        $this->orderPatchService
            ->expects(static::once())
            ->method('patchOrder')
            ->with(
                $paymentTransaction->getOrder(),
                $paymentTransaction->getOrderTransaction(),
                $salesChannelContext,
                'paypalOrderId',
                PartnerAttributionId::PAYPAL_PPCP
            );

        $this->orderTransactionStateHandler
            ->expects(static::once())
            ->method('processUnconfirmed')
            ->with($paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->settingsValidationService
            ->expects(static::once())
            ->method('validate')
            ->with($salesChannelContext->getSalesChannelId());

        $this->handler->pay(
            $paymentTransaction,
            new RequestDataBag([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']),
            $salesChannelContext,
        );
    }

    public function testPayWithInvalidSettingsException(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();

        $this->transactionDataService
            ->expects(static::never())
            ->method('setOrderId');

        $this->orderPatchService
            ->expects(static::never())
            ->method('patchOrder');

        $this->settingsValidationService
            ->expects(static::once())
            ->method('validate')
            ->with($salesChannelContext->getSalesChannelId())
            ->willThrowException(new PayPalSettingsInvalidException('clientId'));

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Required setting "clientId" is missing or invalid');
        $this->handler->pay(
            $paymentTransaction,
            new RequestDataBag([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => 'paypalOrderId']),
            $salesChannelContext,
        );
    }

    public function testPayWithoutValidOrderId(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();

        $this->transactionDataService
            ->expects(static::never())
            ->method('setOrderId');

        $this->orderPatchService
            ->expects(static::never())
            ->method('patchOrder');

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment process was interrupted due to the following error:
Missing PayPal order id');
        $this->handler->pay($paymentTransaction, new RequestDataBag(), $salesChannelContext);
    }

    public function testFinalizeInvalid3DSecure(): void
    {
        $paypalOrderId = 'paypalOrderId';

        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $order = $this->createOrderObject();

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($order);

        $this->acdcValidator
            ->expects(static::once())
            ->method('validate')
            ->with($order, $paymentTransaction, $salesChannelContext)
            ->willReturn(false);

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
The asynchronous payment finalize was interrupted due to the following error:
Credit card validation failed, 3D secure was not validated.');
        $this->handler->finalize($paymentTransaction, new Request([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId]), $salesChannelContext);
    }

    public function testFinalizeWithoutOrderId(): void
    {
        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();

        $this->expectException(PaymentException::class);
        $this->expectExceptionMessage('The asynchronous payment finalize was interrupted due to the following error:
Missing PayPal order id');
        $this->handler->finalize($paymentTransaction, new Request([]), $salesChannelContext);
    }

    public function testFinalizeValid3DSecure(): void
    {
        $paypalOrderId = 'paypalOrderId';

        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $order = $this->createOrderObject();

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($order);

        $this->acdcValidator
            ->expects(static::once())
            ->method('validate')
            ->with($order, $paymentTransaction, $salesChannelContext)
            ->willReturn(true);

        $this->orderExecuteService
            ->expects(static::once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $paymentTransaction->getOrderTransaction()->getId(),
                $order,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext(),
                PartnerAttributionId::PAYPAL_PPCP
            )
            ->willReturn($order);

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($order, $paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->handler->finalize($paymentTransaction, new Request([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId]), $salesChannelContext);
    }

    public function testFinalizeFallbackButton(): void
    {
        $paypalOrderId = 'paypalOrderId';

        $salesChannelContext = Generator::createSalesChannelContext();
        $paymentTransaction = $this->createPaymentTransactionStruct();
        $order = $this->createOrderObject();
        $order->getPaymentSource()?->setCard(null);
        $order->getPaymentSource()?->setPaypal(new Paypal());

        $this->orderResource
            ->expects(static::once())
            ->method('get')
            ->willReturn($order);

        $this->acdcValidator
            ->expects(static::never())
            ->method('validate');

        $this->orderExecuteService
            ->expects(static::once())
            ->method('captureOrAuthorizeOrder')
            ->with(
                $paymentTransaction->getOrderTransaction()->getId(),
                $order,
                $salesChannelContext->getSalesChannelId(),
                $salesChannelContext->getContext(),
                PartnerAttributionId::PAYPAL_PPCP
            )
            ->willReturn($order);

        $this->transactionDataService
            ->expects(static::once())
            ->method('setResourceId')
            ->with($order, $paymentTransaction->getOrderTransaction()->getId(), $salesChannelContext->getContext());

        $this->handler->finalize($paymentTransaction, new Request([AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => $paypalOrderId]), $salesChannelContext);
    }

    private function createPaymentTransactionStruct(): AsyncPaymentTransactionStruct
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('orderTransactionId');

        return new AsyncPaymentTransactionStruct(
            $transaction,
            new OrderEntity(),
            'returnUrl'
        );
    }

    private function createOrderObject(): Order
    {
        $order = new Order();
        $order->setId('paypalOrderId');

        $card = new Card();
        $card->setAuthenticationResult(new AuthenticationResult());
        $paymentSource = new PaymentSource();
        $paymentSource->setCard($card);

        $order->setPaymentSource($paymentSource);

        return $order;
    }
}
