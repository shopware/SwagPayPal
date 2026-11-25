<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Method;

use Monolog\Level;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Paypal;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource\Venmo;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\Method\VenmoHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
use Swag\PayPal\DataAbstractionLayer\VaultToken\VaultTokenEntity;
use Swag\PayPal\OrdersApi\Builder\AbstractOrderBuilder;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\Resource\OrderResource;
use Swag\PayPal\Setting\Service\SettingsValidationServiceInterface;
use Swag\PayPal\Test\Helper\PayPalLogTrait;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AbstractPaymentMethodHandler::class)]
class AbstractPaymentMethodHandlerTest extends TestCase
{
    use PayPalLogTrait;

    private const TRANSACTION_ID = '0199633a7f147221b5db96bf83dfc774';
    private const PAYPAL_ORDER_ID = 'paypalOrderId';
    private const RETURN_URL = 'https://example.com/payment/finalize-transaction';

    private OrderResource&MockObject $orderResource;

    private OrderExecuteService&MockObject $orderExecuteService;

    private VaultTokenService&MockObject $vaultTokenService;

    private TransactionDataService&MockObject $transactionDataService;

    private PayPalPaymentHandler $handler;

    private OrderTransactionEntity $orderTransaction;

    private PayerActionRequiredException $payerActionRequired;

    private Order $paypalOrder;

    protected function setUp(): void
    {
        $order = new OrderEntity();
        $order->setId('order-id');
        $order->setOrderNumber('10000');
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customerId');
        $order->setOrderCustomer($orderCustomer);

        $this->orderTransaction = new OrderTransactionEntity();
        $this->orderTransaction->setId(self::TRANSACTION_ID);
        $this->orderTransaction->setOrder($order);

        $this->paypalOrder = new Order();
        $this->paypalOrder->setId(self::PAYPAL_ORDER_ID);
        $this->paypalOrder->setPaymentSource(new PaymentSource());

        $this->orderResource = $this->createMock(OrderResource::class);
        $this->orderExecuteService = $this->createMock(OrderExecuteService::class);
        $this->vaultTokenService = $this->createMock(VaultTokenService::class);
        $this->transactionDataService = $this->createMock(TransactionDataService::class);

        $captureRejection = new PayPalApiException(
            'UNPROCESSABLE_ENTITY',
            'The payer must approve the changed order.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED,
        );
        $this->payerActionRequired = PayerActionRequiredException::payerActionRequired(self::PAYPAL_ORDER_ID, previous: $captureRejection);

        $this->handler = $this->createHandler(PayPalPaymentHandler::class);
    }

    /**
     * @param class-string<AbstractPaymentMethodHandler> $handlerClass
     */
    #[DataProvider('savedTokenPaymentSourceProvider')]
    public function testPayWithSavedTokenSavesItToTheSubscriptions(string $handlerClass, PaymentSource $paymentSource): void
    {
        $context = Context::createDefaultContext();
        $transaction = new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL);

        // without a prepared order, the order is created with the `vault_id` and needs no approval
        $response = new Order();
        $response->setId(self::PAYPAL_ORDER_ID);
        $response->setPaymentSource($paymentSource);

        $vaultToken = new VaultTokenEntity();
        $vaultToken->setId('vaultTokenId');

        $this->vaultTokenService
            ->expects($this->once())
            ->method('getTokenForPayment')
            ->with($transaction, $this->orderTransaction, static::anything(), $context, false)
            ->willReturn($vaultToken);
        $this->orderResource->expects($this->once())->method('create')->willReturn($response);
        $this->transactionDataService
            ->expects($this->once())
            ->method('setVaultTokenId')
            ->with($this->orderTransaction, 'vaultTokenId', $context);
        $this->orderExecuteService->expects($this->once())->method('captureOrAuthorizeOrder')->willReturn($response);
        $this->vaultTokenService->expects($this->once())->method('saveToken');
        $this->vaultTokenService
            ->expects($this->once())
            ->method('saveUsedTokenToSubscriptions')
            ->with($transaction, $this->orderTransaction, $context);

        static::assertNull($this->createHandler($handlerClass)->pay(new Request(), $transaction, $context, null));
    }

    public static function savedTokenPaymentSourceProvider(): \Generator
    {
        // e.g. headless checkouts, or the storefront without Smart Payment Buttons
        $paymentSource = new PaymentSource();
        $paymentSource->setPaypal(new Paypal());
        yield 'PayPal' => [PayPalPaymentHandler::class, $paymentSource];

        $paymentSource = new PaymentSource();
        $paymentSource->setVenmo(new Venmo());
        yield 'Venmo' => [VenmoHandler::class, $paymentSource];
    }

    public function testPayPreservesPayerActionRequiredWhenConfirmationIsRejected(): void
    {
        $this->expectCaptureRequiringPayerAction();

        $confirmationException = new PayPalApiException(
            'UNPROCESSABLE_ENTITY',
            'The order cannot be confirmed.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'ORDER_ALREADY_COMPLETED',
        );
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);

        try {
            $this->handler->pay(
                new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_ORDER_ID]),
                new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL),
                Context::createDefaultContext(),
                null,
            );
        } catch (PayerActionRequiredException $exception) {
            static::assertSame($this->payerActionRequired, $exception);
            static::assertSame('SWAG_PAYPAL__API_PAYER_ACTION_REQUIRED', $exception->getErrorCode());

            $record = $this->assertPayPalLogged(
                LogCode::CHECKOUT_PAYER_ACTION_RECOVERY_FAILED,
                Level::Error,
                [Log::ORDER_TRANSACTION_ID => self::TRANSACTION_ID, Log::PAYPAL_ORDER_ID => self::PAYPAL_ORDER_ID],
                'Could not send the buyer back to PayPal to approve the payment of order {orderNumber} again, the payment fails',
            );
            static::assertSame($confirmationException::class, $record->context[Log::EXCEPTION]['class']);

            return;
        }

        static::fail('Expected the original payer action exception.');
    }

    public function testPayPreservesPayerActionRequiredWhenConfirmationCannotReachPayPal(): void
    {
        $this->expectCaptureRequiringPayerAction();

        $confirmationException = new class('Connection timed out') extends \RuntimeException implements ClientExceptionInterface {};
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);

        try {
            $this->handler->pay(
                new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_ORDER_ID]),
                new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL),
                Context::createDefaultContext(),
                null,
            );
            static::fail('Expected the original payer action exception.');
        } catch (PayerActionRequiredException $exception) {
            static::assertSame($this->payerActionRequired, $exception);
        }

        $record = $this->assertPayPalLogged(
            LogCode::CHECKOUT_PAYER_ACTION_RECOVERY_FAILED,
            Level::Error,
            [Log::ORDER_TRANSACTION_ID => self::TRANSACTION_ID, Log::PAYPAL_ORDER_ID => self::PAYPAL_ORDER_ID],
            'Could not send the buyer back to PayPal to approve the payment of order {orderNumber} again, the payment fails',
        );
        static::assertSame($confirmationException::class, $record->context[Log::EXCEPTION]['class']);
    }

    public function testPayLogsBuyerCausedConfirmationFailureAsNotice(): void
    {
        $this->expectCaptureRequiringPayerAction();

        $confirmationException = new PayPalApiException(
            'UNPROCESSABLE_ENTITY',
            'The instrument was declined.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            ApiException::CODE_INSTRUMENT_DECLINED,
        );
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);

        try {
            $this->handler->pay(
                new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_ORDER_ID]),
                new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL),
                Context::createDefaultContext(),
                null,
            );
            static::fail('Expected the original payer action exception.');
        } catch (PayerActionRequiredException $exception) {
            static::assertSame($this->payerActionRequired, $exception);
        }

        $this->assertPayPalLogged(LogCode::CHECKOUT_PAYER_ACTION_RECOVERY_FAILED, Level::Notice);
    }

    public function testPayDoesNotHideUnexpectedConfirmationErrors(): void
    {
        $this->expectCaptureRequiringPayerAction();

        $confirmationException = new \LogicException('Unexpected confirmation response');
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);
        try {
            $this->handler->pay(
                new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_ORDER_ID]),
                new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL),
                Context::createDefaultContext(),
                null,
            );
            static::fail('Expected the unexpected confirmation exception.');
        } catch (\LogicException $exception) {
            static::assertSame($confirmationException, $exception);
        }

        $this->assertPayPalNotLogged(LogCode::CHECKOUT_PAYER_ACTION_RECOVERY_FAILED);
    }

    /**
     * @template T of AbstractPaymentMethodHandler
     *
     * @param class-string<T> $handlerClass
     *
     * @return T
     */
    private function createHandler(string $handlerClass): AbstractPaymentMethodHandler
    {
        $orderBuilder = static::createStub(AbstractOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturn($this->paypalOrder);

        return new $handlerClass(
            static::createStub(SettingsValidationServiceInterface::class),
            static::createStub(StateMachineRegistry::class),
            $this->orderExecuteService,
            static::createStub(OrderPatchService::class),
            $this->transactionDataService,
            $this->orderResource,
            $this->vaultTokenService,
            new StaticEntityRepository([new OrderTransactionCollection([$this->orderTransaction])]),
            $this->createPayPalLogger(),
            $orderBuilder,
        );
    }

    private function expectCaptureRequiringPayerAction(): void
    {
        $this->orderResource->expects($this->once())->method('get')->willReturn($this->paypalOrder);
        $this->orderExecuteService->expects($this->once())
            ->method('captureOrAuthorizeOrder')
            ->willThrowException($this->payerActionRequired);
    }
}
