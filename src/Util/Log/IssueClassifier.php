<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Framework\Log\Package;

/**
 * Tells who has to act on a PayPal error, `null` means none of the categories applies, e.g. a technical error.
 * A copy of the PayPal SDK's `IssueClassifier`, as this line doesn't use the SDK: keep both in sync.
 *
 * @internal
 */
#[Package('checkout')]
final class IssueClassifier
{
    /**
     * One response can carry several issues, so the first matching category wins: most specific on top.
     * The keys are literal category values, as PHP 8.1 doesn't allow `IssueCategory::MERCHANT->value` in constant expressions.
     */
    private const CATEGORIES = [
        'MERCHANT' => [
            'PAYEE_ACCOUNT_RESTRICTED',
            'PAYEE_ACCOUNT_LOCKED_OR_CLOSED',
            'PAYEE_ACCOUNT_NOT_SUPPORTED',
            'PAYEE_ACCOUNT_NOT_VERIFIED',
            'PAYEE_NOT_CONSENTED',
            'PERMISSION_NOT_GRANTED',
            'PARTNER_OR_MERCHANT_RESTRICTED_FOR_COUNTRY',
            'TRANSACTION_RECEIVING_LIMIT_EXCEEDED',
            'UNSUPPORTED_INTENT_FOR_KYC_INCOMPLETE_PAYEE',
            'REFUND_FAILED_INSUFFICIENT_FUNDS',
            // products the account is not enabled for
            'NOT_ENABLED_FOR_CARD_PROCESSING',
            'PAYEE_NOT_ENABLED_FOR_CARD_PROCESSING',
            'NOT_ENABLED_FOR_BANK_PROCESSING',
            'PAYEE_NOT_ENABLED_FOR_BANK_PROCESSING',
            'SETUP_ERROR_FOR_BANK',
            'NOT_ENABLED_FOR_APPLE_PAY',
            'NOT_ENABLED_FOR_GOOGLE_PAY',
            'NOT_ENABLED_FOR_PAYMENT_SOURCE',
            'NOT_ENABLED_TO_VAULT_PAYMENT_SOURCE',
            'AUTH_CAPTURE_NOT_ENABLED',
            'DOMAIN_NOT_REGISTERED',
            'NOT_ENABLED_FOR_PUI_PROCESSING',
            'MERCHANT_MISSING_SUBSCRIPTION',
            'DOMAIN_REGISTERED_WITH_ANOTHER_MERCHANT',
            'DOMAIN_VERIFICATION_FAILED_BY_PROVIDER',
            'FAILED_TO_PULL_DOMAIN_VERIFICATION_FILE',
            'INCORRECT_DOMAIN_VERIFICATION_FILE',
            'closed_user',
            'RISK_DECLINE',
        ],
        'BUYER_RESOLVABLE' => [
            'PAYER_ACTION_REQUIRED',
            'ORDER_NOT_APPROVED',
            'REDIRECT_PAYER_FOR_ALTERNATE_FUNDING',
            'INVALID_SECURITY_CODE',
            'INVALID_SECURITY_CODE_LENGTH',
            'PAYMENT_SOURCE_INFO_CANNOT_BE_VERIFIED',
            'POSTAL_CODE_REQUIRED',
            'INVALID_POSTAL_CODE',
            'INVALID_POSTAL_CODE_LENGTH',
            'CITY_REQUIRED',
            'SHIPPING_ADDRESS_INVALID',
            'INVALID_SHIPPING_ADDRESS',
            'BILLING_ADDRESS_INVALID',
            'INVALID_BILLING_ADDRESS',
            'ADDRESS_VALIDATION_ERROR',
        ],
        'BUYER' => [
            // payment method
            'INSTRUMENT_DECLINED',
            'PAYMENT_SOURCE_DECLINED_BY_PROCESSOR',
            'PAYMENT_SOURCE_CANNOT_BE_USED',
            'CONTINGENCY_NOT_SUCCESSFUL',
            'CARD_EXPIRED',
            'REFERENCED_CARD_EXPIRED',
            'CARD_BRAND_NOT_SUPPORTED',
            'CARD_TYPE_NOT_SUPPORTED',
            'CARD_COUNTRY_NOT_SUPPORTED',
            'CARD_ISSUER_COUNTRY_NOT_SUPPORTED',
            'CARD_DATA_NOT_FOUND',
            'CURRENCY_NOT_SUPPORTED_FOR_CARD_TYPE',
            'CURRENCY_NOT_SUPPORTED_FOR_CARD_BRAND',
            // payer account and risk checks, including the merchant's own fraud rules
            'PAYER_CANNOT_PAY',
            'PAYER_NOT_SUPPORTED_BY_PAYMENT_SOURCE',
            'PAYER_ACCOUNT_LOCKED_OR_CLOSED',
            'PAYER_ACCOUNT_RESTRICTED',
            'DOMESTIC_TRANSACTION_REQUIRED',
            'MAX_NUMBER_OF_PAYMENT_ATTEMPTS_EXCEEDED',
            'TRANSACTION_LIMIT_EXCEEDED',
            'TRANSACTION_REFUSED',
            'PAYMENT_DENIED',
            'COMPLIANCE_VIOLATION',
            'PAYEE_BLOCKED_TRANSACTION',
            'TRANSACTION_BLOCKED_BY_PAYEE',
        ],
        'UNRESOLVABLE' => [
            'ORDER_EXPIRED',
            'AUTHORIZATION_EXPIRED',
        ],
        'LOGIC' => [
            // amounts
            'AMOUNT_MISMATCH',
            'ITEM_TOTAL_MISMATCH',
            'TAX_TOTAL_MISMATCH',
            'DISCOUNT_TOTAL_MISMATCH',
            'ITEM_TOTAL_REQUIRED',
            'TAX_TOTAL_REQUIRED',
            'APPLE_PAY_AMOUNT_MISMATCH',
            'DECIMAL_PRECISION',
            'DECIMALS_NOT_SUPPORTED',
            'CANNOT_BE_ZERO_OR_NEGATIVE',
            'CANNOT_BE_NEGATIVE',
            'MAX_VALUE_EXCEEDED',
            'MULTI_CURRENCY_ORDER',
            // shipping options
            'SHIPPING_OPTION_NOT_SELECTED',
            'MULTIPLE_SHIPPING_OPTION_SELECTED',
            'DUPLICATE_SHIPPING_OPTION_ID',
            'PREFERRED_SHIPPING_OPTION_AMOUNT_MISMATCH',
            // parameters
            'MISSING_REQUIRED_PARAMETER',
            'INVALID_PARAMETER_VALUE',
            'INCOMPATIBLE_PARAMETER_VALUE',
            'PAYPAL_REQUEST_ID_REQUIRED',
            'PAYEE_ACCOUNT_INVALID',
            'INVALID_PAYEE_ACCOUNT',
            // error names of malformed requests, after the issues as they also carry buyer issues
            'INVALID_REQUEST',
            'MEDIA_TYPE_NOT_ACCEPTABLE',
            'UNSUPPORTED_MEDIA_TYPE',
            'METHOD_NOT_SUPPORTED',
        ],
    ];

    /**
     * @param string|null ...$codes error name and issues of one response, e.g. `UNPROCESSABLE_ENTITY` and `INSTRUMENT_DECLINED`
     */
    public static function classifyCodes(?string ...$codes): ?IssueCategory
    {
        foreach (self::CATEGORIES as $category => $categoryCodes) {
            if (\array_intersect($codes, $categoryCodes)) {
                return IssueCategory::from($category);
            }
        }

        return null;
    }
}
