<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Test\Util\Log;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Util\Log\IssueCategory;
use Swag\PayPal\Util\Log\IssueClassifier;

/**
 * The cases of the PayPal SDK's `IssueClassifierTest`, for the copy of its classifier.
 *
 * @internal
 */
#[Package('checkout')]
#[CoversClass(IssueClassifier::class)]
class IssueClassifierTest extends TestCase
{
    /**
     * @param list<string> $issues
     */
    #[DataProvider('provideClassifyCodes')]
    public function testClassifyCodes(?IssueCategory $expected, string $name, array $issues = []): void
    {
        static::assertSame($expected, IssueClassifier::classifyCodes($name, ...$issues));
    }

    public static function provideClassifyCodes(): \Generator
    {
        yield 'merchant' => [IssueCategory::MERCHANT, 'UNPROCESSABLE_ENTITY', ['PAYEE_ACCOUNT_RESTRICTED']];
        yield 'closed merchant account as oauth error' => [IssueCategory::MERCHANT, 'closed_user'];
        yield 'risk decline of the token request' => [IssueCategory::MERCHANT, 'RISK_DECLINE'];
        yield 'merchant balance for refund' => [IssueCategory::MERCHANT, 'UNPROCESSABLE_ENTITY', ['REFUND_FAILED_INSUFFICIENT_FUNDS']];
        yield 'buyer resolvable' => [IssueCategory::BUYER_RESOLVABLE, 'UNPROCESSABLE_ENTITY', ['POSTAL_CODE_REQUIRED']];
        yield 'buyer' => [IssueCategory::BUYER, 'UNPROCESSABLE_ENTITY', ['INSTRUMENT_DECLINED']];
        yield 'mistyped security code' => [IssueCategory::BUYER_RESOLVABLE, 'UNPROCESSABLE_ENTITY', ['INVALID_SECURITY_CODE']];
        yield 'unverifiable buyer data' => [IssueCategory::BUYER_RESOLVABLE, 'UNPROCESSABLE_ENTITY', ['PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED']];
        yield 'address issue known from Shopware Payments' => [IssueCategory::BUYER_RESOLVABLE, 'UNPROCESSABLE_ENTITY', ['ADDRESS_VALIDATION_ERROR']];
        yield 'payer transaction limit' => [IssueCategory::BUYER, 'UNPROCESSABLE_ENTITY', ['TRANSACTION_LIMIT_EXCEEDED']];
        yield 'invalid payee in request' => [IssueCategory::LOGIC, 'UNPROCESSABLE_ENTITY', ['PAYEE_ACCOUNT_INVALID']];
        yield 'most specific issue wins' => [IssueCategory::BUYER_RESOLVABLE, 'UNPROCESSABLE_ENTITY', ['INSTRUMENT_DECLINED', 'POSTAL_CODE_REQUIRED']];
        yield 'unresolvable' => [IssueCategory::UNRESOLVABLE, 'UNPROCESSABLE_ENTITY', ['AUTHORIZATION_EXPIRED']];
        yield 'charged back reference' => [null, 'UNPROCESSABLE_ENTITY', ['PREVIOUS_TRANSACTION_REFERENCE_HAS_CHARGEBACK']];
        yield 'voided reference' => [null, 'UNPROCESSABLE_ENTITY', ['PREVIOUS_TRANSACTION_REFERENCE_VOIDED']];
        yield 'retried capture of a captured order' => [null, 'UNPROCESSABLE_ENTITY', ['ORDER_ALREADY_CAPTURED']];
        yield 'unresolvable wins over malformed request' => [IssueCategory::UNRESOLVABLE, 'INVALID_REQUEST', ['AUTHORIZATION_EXPIRED']];
        yield 'logic' => [IssueCategory::LOGIC, 'UNPROCESSABLE_ENTITY', ['ITEM_TOTAL_MISMATCH']];
        yield 'malformed request' => [IssueCategory::LOGIC, 'INVALID_REQUEST', ['INVALID_STRING_LENGTH']];
        yield 'buyer issue wins over malformed request' => [IssueCategory::BUYER_RESOLVABLE, 'INVALID_REQUEST', ['POSTAL_CODE_REQUIRED']];
        yield 'technical issue' => [null, 'UNPROCESSABLE_ENTITY', ['DUPLICATE_INVOICE_ID']];
        yield 'credentials' => [null, 'invalid_client'];
    }

    public function testClassifiesWithoutCodes(): void
    {
        static::assertNull(IssueClassifier::classifyCodes(null, 'RESOURCE_NOT_FOUND'));
        static::assertNull(IssueClassifier::classifyCodes());
    }
}
