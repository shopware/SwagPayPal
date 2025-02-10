<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Card\Exception;

use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CardValidationFailedException extends PaymentException
{
    final public const PAYMENT_CARD_VALIDATION_FAILED = 'SWAG_PAYPAL__CARD_VALIDATION_FAILED';

    public static function cardValidationFailed(string $orderTransactionId, ?string $errorMessage = null): PaymentException
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::PAYMENT_CARD_VALIDATION_FAILED,
            '{{ errorMessage }}',
            [
                'errorMessage' => $errorMessage ?? 'Credit card validation failed, 3D secure was not validated.',
                'orderTransactionId' => $orderTransactionId,
            ],
        );
    }
}
