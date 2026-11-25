<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Log;

use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\ShopwareHttpException;
use Swag\PayPal\Checkout\Card\Exception\CardValidationFailedException;
use Swag\PayPal\Checkout\Exception\EmptyCartException;
use Swag\PayPal\Checkout\Exception\OrderFailedException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;
use Swag\PayPal\RestApi\V2\PaymentStatusV2 as ConstantsV2;

/**
 * Who has to act on a failure, based on `IssueClassifier`: buyer failures are logged lower, merchant account failures critical.
 *
 * @internal
 */
#[Package('checkout')]
final class FailureClassifier
{
    // cart exceptions with other codes are system failures, e.g. a cart that couldn't be loaded
    private const BUYER_ERROR_CODES = [
        CartException::CUSTOMER_NOT_LOGGED_IN_CODE,
        // `CartException::CART_EMPTY` exists from Shopware 6.6.8.0
        'CHECKOUT__CART_EMPTY',
        CardValidationFailedException::PAYMENT_CARD_VALIDATION_FAILED,
        EmptyCartException::ERROR_CODE,
        PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL,
    ];

    // PayPal or the card issuer refused the payment, as with `INSTRUMENT_DECLINED`
    private const REFUSED_PAYMENT_STATUSES = [
        ConstantsV2::ORDER_CAPTURE_DECLINED,
        ConstantsV2::ORDER_AUTHORIZATION_DENIED,
    ];

    public static function isCausedByBuyer(\Throwable $e): bool
    {
        if ($e instanceof OrderFailedException && \in_array($e->getStatus(), self::REFUSED_PAYMENT_STATUSES, true)) {
            return true;
        }

        if ($e instanceof PayPalApiException) {
            // the exception has only the last issue of the response
            $category = IssueClassifier::classifyCodes($e->getName(), $e->getIssue());
            if ($category === IssueCategory::BUYER || $category === IssueCategory::BUYER_RESOLVABLE) {
                return true;
            }

            // the classifier counts it as a code issue, the plugin asks the buyer to change the cart or payment method
            if ($e->is('CANNOT_BE_ZERO_OR_NEGATIVE')) {
                return true;
            }
        }

        return $e instanceof ShopwareHttpException && \in_array($e->getErrorCode(), self::BUYER_ERROR_CODES, true);
    }

    public static function isCancelledByBuyer(\Throwable $e): bool
    {
        return $e instanceof ShopwareHttpException && $e->getErrorCode() === PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL;
    }

    /**
     * @param string|null ...$codes the error name and issues of one response
     */
    public static function isMerchantAccountIssue(?string ...$codes): bool
    {
        return IssueClassifier::classifyCodes(...$codes) === IssueCategory::MERCHANT;
    }
}
