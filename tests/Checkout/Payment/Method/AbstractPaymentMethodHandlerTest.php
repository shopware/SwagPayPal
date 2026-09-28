<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Checkout\Payment\Method;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;
use Shopware\Core\Test\TestDefaults;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PaymentSource;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\Checkout\Payment\Method\AbstractPaymentMethodHandler;
use Swag\PayPal\Checkout\Payment\PayPalPaymentHandler;
use Swag\PayPal\Checkout\Payment\Service\OrderExecuteService;
use Swag\PayPal\Checkout\Payment\Service\OrderPatchService;
use Swag\PayPal\Checkout\Payment\Service\TransactionDataService;
use Swag\PayPal\Checkout\Payment\Service\VaultTokenService;
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

    private LoggerInterface&MockObject $logger;

    private PayPalPaymentHandler $handler;

    private PayerActionRequiredException $payerActionRequired;

    protected function setUp(): void
    {
        $order = new OrderEntity();
        $order->setSalesChannelId(TestDefaults::SALES_CHANNEL);

        $orderTransaction = new OrderTransactionEntity();
        $orderTransaction->setId(self::TRANSACTION_ID);
        $orderTransaction->setOrder($order);

        $paypalOrder = new Order();
        $paypalOrder->setId(self::PAYPAL_ORDER_ID);
        $paypalOrder->setPaymentSource(new PaymentSource());

        $this->orderResource = $this->createMock(OrderResource::class);
        $this->orderResource->expects($this->once())->method('get')->willReturn($paypalOrder);

        $captureRejection = new PayPalApiException(
            'UNPROCESSABLE_ENTITY',
            'The payer must approve the changed order.',
            Response::HTTP_UNPROCESSABLE_ENTITY,
            PayerActionRequiredException::ISSUE_PAYER_ACTION_REQUIRED,
        );
        $this->payerActionRequired = PayerActionRequiredException::payerActionRequired(self::PAYPAL_ORDER_ID, previous: $captureRejection);

        $orderExecuteService = $this->createMock(OrderExecuteService::class);
        $orderExecuteService->expects($this->once())
            ->method('captureOrAuthorizeOrder')
            ->willThrowException($this->payerActionRequired);

        $orderBuilder = static::createStub(AbstractOrderBuilder::class);
        $orderBuilder->method('getOrder')->willReturn($paypalOrder);

        $this->handler = new PayPalPaymentHandler(
            static::createStub(SettingsValidationServiceInterface::class),
            static::createStub(StateMachineRegistry::class),
            $orderExecuteService,
            static::createStub(OrderPatchService::class),
            static::createStub(TransactionDataService::class),
            $this->orderResource,
            static::createStub(VaultTokenService::class),
            new StaticEntityRepository([new OrderTransactionCollection([$orderTransaction])]),
            $orderBuilder,
        );

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler->setLogger($this->logger);
    }

    public function testPayPreservesPayerActionRequiredWhenConfirmationIsRejected(): void
    {
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
}
