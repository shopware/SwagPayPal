<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
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
use Shopware\PayPalSDK\Contract\Struct\V2\Order\PaymentSource\VaultablePaymentSourceInterface;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(AbstractPaymentMethodHandler::class)]
class AbstractPaymentMethodHandlerTest extends TestCase
{
    private const TRANSACTION_ID = '0199633a7f147221b5db96bf83dfc774';
    private const PAYPAL_ORDER_ID = 'paypalOrderId';
    private const RETURN_URL = 'https://example.com/payment/finalize-transaction';

    private OrderResource&MockObject $orderResource;

    private OrderExecuteService&MockObject $orderExecuteService;

    private VaultTokenService&MockObject $vaultTokenService;

    private LoggerInterface&MockObject $logger;

    private PayPalPaymentHandler $handler;

    private PayerActionRequiredException $payerActionRequired;

    private OrderEntity $order;

    private OrderTransactionEntity $orderTransaction;

    private Order $paypalOrder;

    protected function setUp(): void
    {
        $this->order = new OrderEntity();
        $this->order->setSalesChannelId(TestDefaults::SALES_CHANNEL);
        $orderCustomer = new OrderCustomerEntity();
        $orderCustomer->setCustomerId('customerId');
        $this->order->setOrderCustomer($orderCustomer);

        $this->orderTransaction = new OrderTransactionEntity();
        $this->orderTransaction->setId(self::TRANSACTION_ID);
        $this->orderTransaction->setOrder($this->order);

        $this->paypalOrder = new Order();
        $this->paypalOrder->setId(self::PAYPAL_ORDER_ID);
        $this->paypalOrder->setPaymentSource(new PaymentSource());

        $this->orderResource = $this->createMock(OrderResource::class);
        $this->orderExecuteService = $this->createMock(OrderExecuteService::class);
        $this->vaultTokenService = $this->createMock(VaultTokenService::class);

        $captureRejection = new PayPalApiException(
            'UNPROCESSABLE_ENTITY',
            'The payer must approve the changed order.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED,
        );
        $this->payerActionRequired = PayerActionRequiredException::payerActionRequired(self::PAYPAL_ORDER_ID, previous: $captureRejection);

        $this->handler = $this->createHandler(PayPalPaymentHandler::class);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler->setLogger($this->logger);
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

        $this->vaultTokenService->expects($this->once())->method('getAvailableToken')->willReturn(new VaultTokenEntity());
        $this->orderResource->expects($this->once())->method('create')->willReturn($response);
        $this->orderExecuteService->expects($this->once())->method('captureOrAuthorizeOrder')->willReturn($response);
        $this->vaultTokenService->expects($this->once())->method('saveToken');
        $this->vaultTokenService
            ->expects($this->once())
            ->method('saveUsedTokenToSubscriptions')
            ->with($transaction, $this->orderTransaction, $this->order, $paymentSource->first(VaultablePaymentSourceInterface::class), $context);

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
            'ORDER_NOT_APPROVED',
        );
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);
        $this->logger->expects($this->once())->method('warning')->with(
            'Could not reopen the PayPal order for payer approval.',
            [
                'orderTransactionId' => self::TRANSACTION_ID,
                'payPalOrderId' => self::PAYPAL_ORDER_ID,
                'exception' => $confirmationException,
            ],
        );

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

            return;
        }

        static::fail('Expected the original payer action exception.');
    }

    public function testPayPreservesPayerActionRequiredWhenConfirmationCannotReachPayPal(): void
    {
        $this->expectCaptureRequiringPayerAction();

        $confirmationException = new class('Connection timed out') extends \RuntimeException implements ClientExceptionInterface {};
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);
        $this->logger->expects($this->once())->method('warning')->with(
            'Could not reopen the PayPal order for payer approval.',
            [
                'orderTransactionId' => self::TRANSACTION_ID,
                'payPalOrderId' => self::PAYPAL_ORDER_ID,
                'exception' => $confirmationException,
            ],
        );

        $this->expectExceptionObject($this->payerActionRequired);

        $this->handler->pay(
            new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_ORDER_ID]),
            new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL),
            Context::createDefaultContext(),
            null,
        );
    }

    public function testPayDoesNotHideUnexpectedConfirmationErrors(): void
    {
        $this->expectCaptureRequiringPayerAction();

        $confirmationException = new \LogicException('Unexpected confirmation response');
        $this->orderResource->expects($this->once())->method('confirm')->willThrowException($confirmationException);
        $this->logger->expects($this->never())->method('warning');

        $this->expectExceptionObject($confirmationException);

        $this->handler->pay(
            new Request([], [AbstractPaymentMethodHandler::PAYPAL_PAYMENT_ORDER_ID_INPUT_NAME => self::PAYPAL_ORDER_ID]),
            new PaymentTransactionStruct(self::TRANSACTION_ID, self::RETURN_URL),
            Context::createDefaultContext(),
            null,
        );
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
            static::createStub(TransactionDataService::class),
            $this->orderResource,
            $this->vaultTokenService,
            new StaticEntityRepository([new OrderTransactionCollection([$this->orderTransaction])]),
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
