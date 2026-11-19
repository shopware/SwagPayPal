<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use GuzzleHttp\Psr7\Response;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SalesChannelApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\PlatformRequest;
use Shopware\PayPalSDK\Exception\ErrorApiException;
use Shopware\PayPalSDK\Exception\ExceptionFactory;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\AgentErrorDetail;
use Shopware\PayPalSDK\Struct\AgenticCommerce\V1\AgentErrorDetailCollection;
use Shopware\PayPalSDK\Struct\Struct;
use Shopware\PayPalSDK\Struct\V2\Order;
use Shopware\PayPalSDK\Struct\V2\Order\PurchaseUnit\Payments\Capture;
use Swag\PayPal\AgenticCommerce\Exception\AgentException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Util\Log\LogArea;
use Swag\PayPal\Util\Log\LogKey;
use Swag\PayPal\Util\Log\PayPalLogProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(PayPalLogProcessor::class)]
class PayPalLogProcessorTest extends TestCase
{
    private const ECHOED_IBAN = 'DE89370400440532013000';

    public function testAreaIsAdded(): void
    {
        $record = $this->createProcessor()($this->createRecord());

        // the caller is a test class, which is not part of a known area
        static::assertSame(LogArea::OTHER, $record->extra['area']);
    }

    public function testPayPalErrorFromNestedSdkException(): void
    {
        $apiException = new ErrorApiException('UNPROCESSABLE_ENTITY', 'reason', new Response(422), 'debug-123');
        $exception = new \RuntimeException('outer', 0, PayPalApiException::from($apiException));
        $record = $this->createProcessor()($this->createRecord(['exception' => $exception]));

        static::assertSame(422, $record->context['httpStatus']);
        static::assertSame('UNPROCESSABLE_ENTITY', $record->context['paypalError']);
        static::assertSame('debug-123', $record->context['paypalDebugId']);
        static::assertArrayNotHasKey('paypalIssues', $record->context);
    }

    public function testPayPalErrorFromTheWrapperWithoutSdkException(): void
    {
        $exception = new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'INSTRUMENT_DECLINED');
        $record = $this->createProcessor()($this->createRecord(['exception' => $exception]));

        static::assertSame(422, $record->context['httpStatus']);
        static::assertSame('UNPROCESSABLE_ENTITY', $record->context['paypalError']);
        static::assertSame(['INSTRUMENT_DECLINED'], $record->context['paypalIssues']);
        static::assertArrayNotHasKey('paypalDebugId', $record->context);
    }

    public function testSdkExceptionLoggedDirectly(): void
    {
        $apiException = ExceptionFactory::createFromResponse(new Response(422, [], \json_encode([
            'name' => 'UNPROCESSABLE_ENTITY',
            'message' => 'Some reason',
            'debug_id' => 'debug-123',
            'details' => [['issue' => 'CARD_EXPIRED'], ['issue' => 'INSTRUMENT_DECLINED']],
        ], \JSON_THROW_ON_ERROR)));
        $record = $this->createProcessor()($this->createRecord(['exception' => $apiException]));

        static::assertSame('SWAG_PAYPAL__API_INSTRUMENT_DECLINED', $record->context['errorCode']);
        static::assertSame(['CARD_EXPIRED', 'INSTRUMENT_DECLINED'], $record->context['paypalIssues']);
        static::assertSame($apiException->getMessage(), $record->context['exception']['message'] ?? null);
    }

    public function testExistingContextIsKept(): void
    {
        $apiException = new ErrorApiException('UNPROCESSABLE_ENTITY', 'reason', new Response(422), 'debug-123');
        $record = $this->createProcessor()($this->createRecord([
            'exception' => PayPalApiException::from($apiException),
            'paypalDebugId' => 'mine',
            'paypalIssues' => ['MINE'],
        ]));

        static::assertSame('mine', $record->context['paypalDebugId']);
        static::assertSame(['MINE'], $record->context['paypalIssues']);
    }

    public function testErrorCodeOfShopwareException(): void
    {
        $exception = new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'INSTRUMENT_DECLINED');
        $record = $this->createProcessor()($this->createRecord(['exception' => $exception]));

        static::assertSame('SWAG_PAYPAL__API_INSTRUMENT_DECLINED', $record->context['errorCode']);
    }

    public function testErrorCodeOfPlainException(): void
    {
        $record = $this->createProcessor()($this->createRecord(['exception' => new \RuntimeException()]));

        static::assertSame('RuntimeException', $record->context['errorCode']);
    }

    public function testExistingErrorCodeIsKept(): void
    {
        $record = $this->createProcessor()($this->createRecord(['exception' => new \RuntimeException(), 'errorCode' => 'MINE']));

        static::assertSame('MINE', $record->context['errorCode']);
    }

    public function testNoErrorCodeWithoutException(): void
    {
        $record = $this->createProcessor()($this->createRecord());

        static::assertArrayNotHasKey('errorCode', $record->context);
    }

    public function testExceptionIsReplacedByAnArray(): void
    {
        $inner = new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'INSTRUMENT_DECLINED');
        $exception = new \LogicException('outer', 0, $inner);

        $record = $this->createProcessor()($this->createRecord([LogKey::EXCEPTION => $exception]));

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
        ], $record->context[LogKey::EXCEPTION]);
    }

    #[DataProvider('provideExceptionsWithAnEchoedValue')]
    public function testSubmittedValuesOfRejectedFieldsAreNotLogged(\Throwable $exception): void
    {
        $record = $this->createProcessor()($this->createRecord([LogKey::EXCEPTION => $exception]));

        static::assertStringNotContainsString(self::ECHOED_IBAN, \json_encode($record->toArray(), \JSON_THROW_ON_ERROR));
        static::assertSame(['INVALID_IBAN'], $record->context[LogKey::PAYPAL_ISSUES]);
        static::assertSame('debug-123', $record->context[LogKey::PAYPAL_DEBUG_ID]);
        static::assertStringContainsString('[INVALID_IBAN] (/payment_source/sepa_debit/iban) The IBAN is invalid.', $record->context[LogKey::EXCEPTION]['message'] ?? '');
    }

    /**
     * @return iterable<string, array{\Throwable}>
     */
    public static function provideExceptionsWithAnEchoedValue(): iterable
    {
        $apiException = ExceptionFactory::createFromResponse(new Response(422, ['paypal-debug-id' => 'debug-123'], \json_encode([
            'name' => 'UNPROCESSABLE_ENTITY',
            'message' => 'The requested action could not be performed.',
            'debug_id' => 'debug-123',
            'details' => [['field' => '/payment_source/sepa_debit/iban', 'value' => self::ECHOED_IBAN, 'issue' => 'INVALID_IBAN', 'description' => 'The IBAN is invalid.']],
        ], \JSON_THROW_ON_ERROR)));

        yield 'SDK exception' => [$apiException];
        yield 'wrapped SDK exception' => [PayPalApiException::from($apiException)];
    }

    public function testParametersOfTheExceptionAreRedacted(): void
    {
        $exception = new class('No customer with email {{ email }}', ['email' => 'buyer@example.com', 'salesChannelId' => 'sc']) extends ShopwareHttpException {
            public function getErrorCode(): string
            {
                return 'TEST__CUSTOMER_NOT_FOUND';
            }
        };

        $record = $this->createProcessor()($this->createRecord([LogKey::EXCEPTION => $exception]));

        static::assertIsArray($record->context[LogKey::EXCEPTION]);
        static::assertSame(['email' => '<redacted>', 'salesChannelId' => 'sc'], $record->context[LogKey::EXCEPTION]['parameters']);
    }

    public function testDetailsOfAgentErrorsAndViolationsAreKept(): void
    {
        $agentException = new AgentException(500, 'TEST_ERROR', 'Test error message', [], new AgentErrorDetailCollection([
            Struct::from(AgentErrorDetail::class, ['field' => 'field1', 'issue' => 'issue1', 'description' => 'description1']),
        ]));
        $violationException = new ConstraintViolationException(new ConstraintViolationList([
            new ConstraintViolation('test message', 'test message', [], '/root', 'testProperty', 'invalid'),
        ]), []);

        $agent = $this->createProcessor()($this->createRecord([LogKey::EXCEPTION => $agentException]))->context[LogKey::EXCEPTION];
        $violation = $this->createProcessor()($this->createRecord([LogKey::EXCEPTION => $violationException]))->context[LogKey::EXCEPTION];

        static::assertIsArray($agent);
        static::assertSame([['field' => 'field1', 'issue' => 'issue1', 'description' => 'description1']], $agent['details']);
        static::assertIsArray($violation);
        static::assertSame(["/root.testProperty:\n    test message"], $violation['violations']);
    }

    public function testSalesChannelFromRequest(): void
    {
        $stack = $this->createStack(new Request(attributes: [PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID => 'sales-channel-id']));

        $record = $this->createProcessor($stack)($this->createRecord());
        static::assertSame('sales-channel-id', $record->context['salesChannelId']);
        static::assertArrayNotHasKey('salesChannelId', $record->extra);

        $record = $this->createProcessor($stack)($this->createRecord(['salesChannelId' => 'from-context']));
        static::assertSame('from-context', $record->context['salesChannelId']);

        $record = $this->createProcessor($stack)($this->createRecord(['salesChannelId' => null]));
        static::assertSame('sales-channel-id', $record->context['salesChannelId']);
    }

    public function testNoRequestNoSalesChannelAndUser(): void
    {
        $record = $this->createProcessor()($this->createRecord());

        static::assertArrayNotHasKey('salesChannelId', $record->context);
        static::assertArrayNotHasKey('userId', $record->context);
    }

    public function testUserFromAdminApiRequest(): void
    {
        $stack = $this->createStack(new Request(attributes: [
            PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT => new Context(new AdminApiSource('user-id')),
        ]));

        $record = $this->createProcessor($stack)($this->createRecord());
        static::assertSame('user-id', $record->context['userId']);

        $record = $this->createProcessor($stack)($this->createRecord(['userId' => 'from-context']));
        static::assertSame('from-context', $record->context['userId']);
    }

    public function testNoUserWithoutAdminApiUser(): void
    {
        foreach ([new AdminApiSource(null), new SystemSource(), new SalesChannelApiSource('sales-channel-id')] as $source) {
            $stack = $this->createStack(new Request(attributes: [PlatformRequest::ATTRIBUTE_CONTEXT_OBJECT => new Context($source)]));

            static::assertArrayNotHasKey('userId', $this->createProcessor($stack)($this->createRecord())->context);
        }
    }

    public function testNullContextEntriesAreDropped(): void
    {
        $record = $this->createProcessor()($this->createRecord([
            'orderNumber' => null,
            'fatal' => false,
            'amount' => '0',
            'trackingCodes' => [null],
        ]));

        static::assertSame(['fatal' => false, 'amount' => '0', 'trackingCodes' => [null]], $record->context);
    }

    public function testNeverThrows(): void
    {
        $stack = $this->createMock(RequestStack::class);
        $stack->method('getMainRequest')->willThrowException(new \RuntimeException());
        $record = $this->createRecord();

        static::assertEquals($record, $this->createProcessor($stack)($record));
    }

    public function testRedactsBodiesAndPath(): void
    {
        $record = $this->createProcessor()($this->createRecord([
            LogKey::HTTP_PATH => '/v3/vault/payment-tokens/abc',
            LogKey::REQUEST => '{"payer":{"email_address":"buyer@example.com"}}',
            LogKey::RESPONSE => 'grant_type=client_credentials&client_secret=secret',
            LogKey::PAYLOAD => ['firstName' => 'A'],
            LogKey::PAYPAL_ORDER_ID => '5O1',
        ]));

        static::assertSame([
            LogKey::HTTP_PATH => '/v3/vault/payment-tokens/<redacted>',
            LogKey::REQUEST => ['payer' => ['email_address' => '<redacted>']],
            LogKey::RESPONSE => '<redacted>',
            LogKey::PAYLOAD => ['firstName' => '<redacted>'],
            LogKey::PAYPAL_ORDER_ID => '5O1',
        ], $record->context);
    }

    public function testRedactedKeysAreReplacedIfTheProcessorFails(): void
    {
        $stack = $this->createMock(RequestStack::class);
        $stack->method('getMainRequest')->willThrowException(new \RuntimeException());

        $record = $this->createProcessor($stack)($this->createRecord([
            LogKey::REQUEST => ['email' => 'a@b.c'],
            LogKey::HTTP_PATH => '/v3/vault/payment-tokens/abc',
            LogKey::PAYPAL_ORDER_ID => '5O1',
        ]));

        static::assertSame([
            LogKey::REQUEST => '<redacted>',
            LogKey::HTTP_PATH => '<redacted>',
            LogKey::PAYPAL_ORDER_ID => '5O1',
        ], $record->context);
    }

    public function testObjectsAreReplacedByTheirKeys(): void
    {
        $transaction = new OrderTransactionEntity();
        $transaction->setId('transaction-id');

        $record = $this->createProcessor()($this->createRecord([
            LogKey::CODE => 'CODE',
            LogKey::ORDER_TRANSACTION => $transaction,
            LogKey::PAYMENT => (new Capture())->assign(['id' => 'CAP-1']),
        ]));

        static::assertSame([
            LogKey::ORDER_TRANSACTION_ID => 'transaction-id',
            LogKey::CAPTURE_ID => 'CAP-1',
            LogKey::CODE => 'CODE',
        ], $record->context);
    }

    public function testObjectsAreDroppedIfTheProcessorFails(): void
    {
        $stack = $this->createMock(RequestStack::class);
        $stack->method('getMainRequest')->willThrowException(new \RuntimeException());

        $record = $this->createProcessor($stack)($this->createRecord([
            LogKey::PAYPAL_ORDER => (new Order())->assign(['id' => '5O1']),
            LogKey::PAYPAL_ORDER_ID => '5O1',
        ]));

        static::assertSame([LogKey::PAYPAL_ORDER_ID => '5O1'], $record->context);
    }

    private function createStack(Request $request): RequestStack
    {
        $stack = new RequestStack();
        $stack->push($request);

        return $stack;
    }

    private function createProcessor(?RequestStack $stack = null): PayPalLogProcessor
    {
        return new PayPalLogProcessor($stack ?? new RequestStack());
    }

    /**
     * @param array<string, mixed> $context
     */
    private function createRecord(array $context = []): LogRecord
    {
        return new LogRecord(new \DateTimeImmutable(), 'paypal', Level::Info, 'message', $context);
    }
}
