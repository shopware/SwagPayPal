<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout;

use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CheckoutException extends HttpException
{
    public const PAYPAL_ORDER_ALREADY_USED = 'PAYPAL_ORDER_ALREADY_USED';

    public static function paypalOrderAlreadyUsed(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::PAYPAL_ORDER_ALREADY_USED,
            'The PayPal Order ID has already been used for another order.',
        );
    }
}
