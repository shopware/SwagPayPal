<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use GuzzleHttp\Psr7\Response as PsrResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Exception\ExceptionFactory;
use Shopware\PayPalSDK\Struct\ConstantsV2;
use Swag\PayPal\Checkout\Exception\EmptyCartException;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\Util\Log\FailureClassifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * @internal
 */
#[Package('checkout')]
#[CoversClass(FailureClassifier::class)]
class FailureClassifierTest extends TestCase
{
    #[DataProvider('buyerCausedProvider')]
    public function testIsCausedByBuyer(\Throwable $e, bool $expected): void
    {
        static::assertSame($expected, FailureClassifier::isCausedByBuyer($e));
    }

    /**
     * @return iterable<string, array{\Throwable, bool}>
     */
    public static function buyerCausedProvider(): iterable
    {
        foreach (['INSTRUMENT_DECLINED', 'PAYER_ACTION_REQUIRED', 'PAYER_CANNOT_PAY', 'TRANSACTION_REFUSED', 'CARD_EXPIRED', 'PAYMENT_DENIED', 'POSTAL_CODE_REQUIRED', 'CANNOT_BE_ZERO_OR_NEGATIVE', 'PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED', 'PAYMENT_SOURCE_DECLINED_BY_PROCESSOR', 'TRANSACTION_LIMIT_EXCEEDED', 'PAYER_ACCOUNT_RESTRICTED', 'PAYEE_BLOCKED_TRANSACTION', 'INVALID_SHIPPING_ADDRESS'] as $issue) {
            yield 'issue ' . $issue => [new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, $issue), true];
        }

        yield 'risk decline of the token request' => [new PayPalApiException('RISK_DECLINE', 'message', 401), false];
        yield 'buyer issue of a malformed request' => [new PayPalApiException('INVALID_REQUEST', 'message', 400, 'POSTAL_CODE_REQUIRED'), true];
        yield 'merchant account issue' => [new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'PAYEE_ACCOUNT_RESTRICTED'), false];
        yield 'code issue' => [new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'ITEM_TOTAL_MISMATCH'), false];
        yield 'other issue' => [new PayPalApiException('UNPROCESSABLE_ENTITY', 'message', 422, 'DUPLICATE_INVOICE_ID'), false];
        yield 'no issue' => [new PayPalApiException('INTERNAL_SERVER_ERROR', 'message'), false];
        yield 'payer action required' => [PayerActionRequiredException::payerActionRequired('ORDER-ID'), true];
        yield 'empty cart' => [new EmptyCartException(), true];
        yield 'declined capture' => [new OrderFailedException('ORDER-ID', ConstantsV2::ORDER_CAPTURE_DECLINED), true];
        yield 'denied authorization' => [new OrderFailedException('ORDER-ID', ConstantsV2::ORDER_AUTHORIZATION_DENIED), true];
        yield 'failed capture' => [new OrderFailedException('ORDER-ID', ConstantsV2::ORDER_CAPTURE_FAILED), false];
        yield 'voided authorization' => [new OrderFailedException('ORDER-ID', ConstantsV2::ORDER_AUTHORIZATION_VOIDED), false];
        yield 'failed order without status' => [new OrderFailedException('ORDER-ID'), false];
        yield 'browser user cancelled code' => [self::httpException('SWAG_PAYPAL__USER_CANCELLED'), false];
        yield 'card validation' => [self::httpException('SWAG_PAYPAL__CARD_VALIDATION_FAILED'), true];
        yield 'customer cancelled' => [PaymentException::customerCanceled('order', 'message'), true];
        yield 'cart exception' => [CartException::deserializeFailed(), true];
        yield 'other http exception' => [self::httpException('SOMETHING_ELSE'), false];
        yield 'runtime exception' => [new \RuntimeException('message'), false];
    }

    public function testAllIssuesOfTheResponseDecide(): void
    {
        $response = static fn (string ...$issues): PsrResponse => new PsrResponse(422, [], \json_encode([
            'name' => 'UNPROCESSABLE_ENTITY',
            'message' => 'Some message',
            'debug_id' => 'debug-123',
            'details' => \array_map(static fn (string $issue): array => ['issue' => $issue], $issues),
        ], \JSON_THROW_ON_ERROR));

        static::assertTrue(FailureClassifier::isCausedByBuyer(PayPalApiException::from(ExceptionFactory::createFromResponse($response('INSTRUMENT_DECLINED')))));
        // the merchant restriction wins, although the wrapper only knows the last issue
        static::assertFalse(FailureClassifier::isCausedByBuyer(PayPalApiException::from(ExceptionFactory::createFromResponse($response('PAYEE_ACCOUNT_RESTRICTED', 'INSTRUMENT_DECLINED')))));
    }

    public function testIsCancelledByBuyer(): void
    {
        static::assertTrue(FailureClassifier::isCancelledByBuyer(PaymentException::customerCanceled('order', 'message')));
        static::assertFalse(FailureClassifier::isCancelledByBuyer(self::httpException('SWAG_PAYPAL__USER_CANCELLED')));
        static::assertFalse(FailureClassifier::isCancelledByBuyer(new EmptyCartException()));
        static::assertFalse(FailureClassifier::isCancelledByBuyer(new \RuntimeException()));
    }

    public function testIsMerchantAccountIssue(): void
    {
        static::assertTrue(FailureClassifier::isMerchantAccountIssue('PAYEE_ACCOUNT_RESTRICTED'));
        static::assertTrue(FailureClassifier::isMerchantAccountIssue('PAYEE_NOT_ENABLED_FOR_CARD_PROCESSING'));
        static::assertTrue(FailureClassifier::isMerchantAccountIssue('RISK_DECLINE'));
        static::assertTrue(FailureClassifier::isMerchantAccountIssue('closed_user'));
        static::assertTrue(FailureClassifier::isMerchantAccountIssue('NOT_ENABLED_FOR_APPLE_PAY'));
        static::assertFalse(FailureClassifier::isMerchantAccountIssue('PAYER_ACCOUNT_RESTRICTED'));
        static::assertFalse(FailureClassifier::isMerchantAccountIssue(null));
        // a merchant issue wins over a buyer issue of the same response
        static::assertTrue(FailureClassifier::isMerchantAccountIssue('UNPROCESSABLE_ENTITY', 'PAYEE_ACCOUNT_RESTRICTED', 'INSTRUMENT_DECLINED'));
    }

    private static function httpException(string $errorCode): HttpException
    {
        return new class($errorCode) extends HttpException {
            public function __construct(string $errorCode)
            {
                parent::__construct(Response::HTTP_BAD_REQUEST, $errorCode, 'message');
            }
        };
    }
}
