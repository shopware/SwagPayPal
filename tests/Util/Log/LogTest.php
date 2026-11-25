<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\AgenticCommerce\Struct\V1\AgentErrorDetail;
use Swag\PayPal\AgenticCommerce\Struct\V1\AgentErrorDetailCollection;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V1\Api\Webhook as Event;
use Swag\PayPal\RestApi\V2\Api\Order;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Amount;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Refund;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnitCollection;
use Swag\PayPal\Util\Log\Log;
use Swag\PayPal\Util\Log\LogCode;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(Log::class)]
class LogTest extends TestCase
{
    public function testDropsEmptyValuesButKeepsFalseAndZero(): void
    {
        static::assertSame(
            [Log::CODE => LogCode::API_REQUEST_FAILED, Log::FATAL => false, Log::SHIPPING_OPTION_COUNT => 0],
            Log::context(code: LogCode::API_REQUEST_FAILED, orderNumber: null, webhookId: '', paypalIssues: [], fatal: false, shippingOptionCount: 0),
        );
    }

    public function testExplicitValuesWin(): void
    {
        $context = Log::context(paypalOrder: (new Order())->assign(['id' => 'FROM-ORDER']), paypalOrderId: 'EXPLICIT');

        static::assertSame('EXPLICIT', $context[Log::PAYPAL_ORDER_ID]);
    }

    public function testValuesSharedByLogLinesAreSpreadIn(): void
    {
        $shared = [Log::WEBHOOK_EVENT => (new Event())->assign(['id' => 'WH-1', 'event_type' => 'PAYMENT.CAPTURE.COMPLETED']), Log::SALES_CHANNEL_ID => 'sales-channel-id'];

        static::assertSame([
            Log::WEBHOOK_EVENT_ID => 'WH-1',
            Log::WEBHOOK_EVENT_TYPE => 'PAYMENT.CAPTURE.COMPLETED',
            Log::CODE => LogCode::API_REQUEST_FAILED,
            Log::SALES_CHANNEL_ID => 'sales-channel-id',
        ], Log::context(...$shared, code: LogCode::API_REQUEST_FAILED));
    }

    public function testRedactsBodiesAndPaths(): void
    {
        static::assertSame([
            Log::HTTP_PATH => '/v3/vault/payment-tokens/<redacted>',
            Log::REQUEST => ['payer' => ['email_address' => '<redacted>']],
            Log::RESPONSE => '<redacted>',
            Log::PAYLOAD => ['resource' => ['payer' => ['name' => '<redacted>']]],
        ], Log::context(
            httpPath: '/v3/vault/payment-tokens/8kk8451t',
            request: '{"payer":{"email_address":"buyer@example.com"}}',
            response: 'client_secret=abc',
            payload: ['resource' => ['payer' => ['name' => ['given_name' => 'Jane']]]],
        ));
    }

    public function testCutsUnvalidatedInput(): void
    {
        $context = Log::context(clientError: \str_repeat('a', 300), webhookEventType: \str_repeat('b', 300), changedKeys: \str_repeat('c', 300));

        static::assertSame(\str_repeat('a', 200), $context[Log::CLIENT_ERROR]);
        static::assertSame(\str_repeat('b', 200), $context[Log::WEBHOOK_EVENT_TYPE]);
        static::assertSame(\str_repeat('c', 300), $context[Log::CHANGED_KEYS]);
    }

    public function testOrderTransaction(): void
    {
        $order = new OrderEntity();
        $order->setId(Uuid::randomHex());
        $order->setOrderNumber('10023');
        $order->setSalesChannelId(Uuid::randomHex());
        $paymentMethod = new PaymentMethodEntity();
        $paymentMethod->setId(Uuid::randomHex());
        $paymentMethod->setTechnicalName('swag_paypal_acdc');
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setOrder($order);
        $transaction->setPaymentMethod($paymentMethod);

        static::assertSame([
            Log::ORDER_TRANSACTION_ID => 'transaction-id',
            Log::ORDER_ID => $order->getId(),
            Log::ORDER_NUMBER => '10023',
            Log::SALES_CHANNEL_ID => $order->getSalesChannelId(),
            Log::PAYMENT_METHOD => 'swag_paypal_acdc',
        ], Log::context(orderTransaction: $transaction));
    }

    public function testOrderTransactionWithoutOrder(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');

        static::assertSame([Log::ORDER_TRANSACTION_ID => 'transaction-id'], Log::context(orderTransaction: $transaction));
    }

    public function testOrderTransactionWithAPartiallyLoadedOrderIsLeftOut(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');
        $transaction->setOrder(new OrderEntity());

        static::assertSame([Log::CODE => LogCode::API_REQUEST_FAILED], Log::context(code: LogCode::API_REQUEST_FAILED, orderTransaction: $transaction));
    }

    public function testPaypalOrder(): void
    {
        $amount = new Amount();
        $amount->setValue('10.00');
        $amount->setCurrencyCode('EUR');
        $purchaseUnit = new PurchaseUnit();
        $purchaseUnit->setAmount($amount);
        $purchaseUnit->setInvoiceId('INV-1');
        $order = new Order();
        $order->setId('5O190127TN364715T');
        $order->setIntent('CAPTURE');
        $order->setPurchaseUnits(new PurchaseUnitCollection([$purchaseUnit]));

        static::assertSame([
            Log::PAYPAL_ORDER_ID => '5O190127TN364715T',
            Log::INTENT => 'CAPTURE',
            Log::AMOUNT => '10.00',
            Log::CURRENCY => 'EUR',
            Log::INVOICE_ID => 'INV-1',
        ], Log::context(paypalOrder: $order));
    }

    public function testPaypalOrderSkipsUnsetValues(): void
    {
        static::assertSame([Log::INTENT => 'CAPTURE'], Log::context(paypalOrder: new Order()));

        $order = new Order();
        $order->setId('ID');
        $order->setPurchaseUnits(new PurchaseUnitCollection([new PurchaseUnit()]));

        static::assertSame([Log::PAYPAL_ORDER_ID => 'ID', Log::INTENT => 'CAPTURE'], Log::context(paypalOrder: $order));
    }

    public function testPayment(): void
    {
        static::assertSame([
            Log::CAPTURE_ID => 'CAP-1',
            Log::CAPTURE_STATUS => 'COMPLETED',
            Log::AMOUNT => '5.00',
            Log::CURRENCY => 'EUR',
        ], Log::context(payment: (new Capture())->assign([
            'id' => 'CAP-1',
            'status' => 'COMPLETED',
            'amount' => ['value' => '5.00', 'currency_code' => 'EUR'],
        ])));

        // a minimal response only has the ID and status, an empty one must not throw either
        static::assertSame([Log::REFUND_ID => 'REF-1'], Log::context(payment: (new Refund())->assign(['id' => 'REF-1'])));
        static::assertSame([], Log::context(payment: new Authorization()));
    }

    public function testWebhookEventWithoutValues(): void
    {
        static::assertSame([], Log::context(webhookEvent: new Event()));
    }

    public function testPayPalErrorFromNestedException(): void
    {
        $context = Log::context(exception: new \RuntimeException('outer', 0, new PayPalApiException('UNPROCESSABLE_ENTITY', 'reason', 422)));

        static::assertSame(422, $context[Log::HTTP_STATUS]);
        static::assertSame('UNPROCESSABLE_ENTITY', $context[Log::PAYPAL_ERROR]);
        static::assertSame('RuntimeException', $context[Log::ERROR_CODE]);
        static::assertArrayNotHasKey(Log::PAYPAL_ISSUES, $context);
    }

    public function testPayPalErrorFromTheWrapperWithoutSdkException(): void
    {
        $context = Log::context(exception: new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'INSTRUMENT_DECLINED'));

        static::assertSame(422, $context[Log::HTTP_STATUS]);
        static::assertSame('UNPROCESSABLE_ENTITY', $context[Log::PAYPAL_ERROR]);
        static::assertSame(['INSTRUMENT_DECLINED'], $context[Log::PAYPAL_ISSUES]);
        static::assertSame('SWAG_PAYPAL__API_INSTRUMENT_DECLINED', $context[Log::ERROR_CODE]);
        static::assertArrayNotHasKey(Log::PAYPAL_DEBUG_ID, $context);
    }

    public function testExplicitValuesWinOverTheException(): void
    {
        $context = Log::context(exception: new PayPalApiException('UNPROCESSABLE_ENTITY', 'reason', 422, 'INSTRUMENT_DECLINED'), errorCode: 'MINE', paypalDebugId: 'mine', paypalIssues: ['MINE']);

        static::assertSame('MINE', $context[Log::ERROR_CODE]);
        static::assertSame('mine', $context[Log::PAYPAL_DEBUG_ID]);
        static::assertSame(['MINE'], $context[Log::PAYPAL_ISSUES]);
    }

    public function testNoErrorCodeWithoutException(): void
    {
        static::assertArrayNotHasKey(Log::ERROR_CODE, Log::context(code: LogCode::API_REQUEST_FAILED));
    }

    public function testExceptionIsNormalized(): void
    {
        $inner = new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'INSTRUMENT_DECLINED');
        $exception = new \LogicException('outer', 0, $inner);

        static::assertSame([
            'class' => \LogicException::class,
            'message' => 'outer',
            'file' => __FILE__,
            'line' => $exception->getLine(),
            'previous' => [
                'class' => PayPalApiException::class,
                'message' => $inner->getMessage(),
                'errorCode' => 'SWAG_PAYPAL__API_INSTRUMENT_DECLINED',
                'parameters' => ['name' => 'UNPROCESSABLE_ENTITY', 'message' => 'message', 'issue' => 'INSTRUMENT_DECLINED'],
                'file' => __FILE__,
                'line' => $inner->getLine(),
            ],
        ], Log::context(exception: $exception)[Log::EXCEPTION]);
    }

    public function testParametersOfTheExceptionAreRedacted(): void
    {
        $exception = new class('No customer with email {{ email }}', ['email' => 'buyer@example.com', 'salesChannelId' => 'sc']) extends ShopwareHttpException {
            public function getErrorCode(): string
            {
                return 'TEST__CUSTOMER_NOT_FOUND';
            }
        };

        static::assertSame(['email' => '<redacted>', 'salesChannelId' => 'sc'], Log::context(exception: $exception)[Log::EXCEPTION]['parameters'] ?? null);
    }

    public function testDetailsOfAgentErrorsAndViolationsAreKept(): void
    {
        $agentException = new AgentException(500, 'TEST_ERROR', 'Test error message', [], new AgentErrorDetailCollection([
            (new AgentErrorDetail())->assign(['field' => 'field1', 'issue' => 'issue1', 'description' => 'description1']),
        ]));
        $violationException = new ConstraintViolationException(new ConstraintViolationList([
            new ConstraintViolation('test message', 'test message', [], '/root', 'testProperty', 'invalid'),
        ]), []);

        static::assertSame(
            [['field' => 'field1', 'issue' => 'issue1', 'description' => 'description1']],
            Log::context(exception: $agentException)[Log::EXCEPTION]['details'] ?? null,
        );
        static::assertSame(
            ["/root.testProperty:\n    test message"],
            Log::context(exception: $violationException)[Log::EXCEPTION]['violations'] ?? null,
        );
    }

    public function testNeverThrows(): void
    {
        $exception = new class('broken') extends ShopwareHttpException {
            public function getErrorCode(): string
            {
                throw new \LogicException('broken exception');
            }
        };

        static::assertSame(
            [Log::CODE => LogCode::API_REQUEST_FAILED, Log::EXCEPTION => ['class' => $exception::class, 'message' => 'broken']],
            Log::context(code: LogCode::API_REQUEST_FAILED, exception: $exception, request: '{"email":"buyer@example.com"}'),
        );
    }

    public function testEveryParameterIsNamedAfterAKey(): void
    {
        $keys = (new \ReflectionClass(Log::class))->getConstants(\ReflectionClassConstant::IS_PUBLIC);

        foreach ((new \ReflectionMethod(Log::class, 'context'))->getParameters() as $parameter) {
            static::assertContains($parameter->getName(), $keys, \sprintf('Log::context() parameter $%s has no key constant', $parameter->getName()));
        }
    }
}
