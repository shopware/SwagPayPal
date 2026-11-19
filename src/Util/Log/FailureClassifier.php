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
use Shopware\PayPalSDK\Exception\ApiException;
use Shopware\PayPalSDK\Util\IssueCategory;
use Shopware\PayPalSDK\Util\IssueClassifier;
use Swag\PayPal\Checkout\Card\Exception\CardValidationFailedException;
use Swag\PayPal\Checkout\Exception\EmptyCartException;
use Swag\PayPal\Checkout\Payment\Exception\PayerActionRequiredException;
use Swag\PayPal\RestApi\Exception\PayPalApiException;

/**
 * Who has to act on a failure, based on the SDK's `IssueClassifier`: buyer failures are logged lower, merchant account failures critical.
 *
 * @internal
 */
#[Package('checkout')]
final class FailureClassifier
{
    private const BUYER_ERROR_CODES = [
        CardValidationFailedException::PAYMENT_CARD_VALIDATION_FAILED,
        EmptyCartException::ERROR_CODE,
        PaymentException::PAYMENT_CUSTOMER_CANCELED_EXTERNAL,
    ];

    public static function isCausedByBuyer(\Throwable $e): bool
    {
        if ($e instanceof PayerActionRequiredException || $e instanceof CartException) {
            return true;
        }

        if ($e instanceof PayPalApiException) {
            $category = IssueClassifier::classifyCodes($e->getName(), $e->getIssue());
            if ($category === IssueCategory::BUYER || $category === IssueCategory::BUYER_RESOLVABLE) {
                return true;
            }

            // the SDK counts it as a code issue, the plugin asks the buyer to change the cart or payment method
            if ($e->is(ApiException::CODE_CANNOT_BE_ZERO_OR_NEGATIVE)) {
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
