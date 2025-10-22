<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class CheckoutException extends HttpException
{
    public const PREPARED_ORDER_REQUIRED = 'PREPARED_ORDER_REQUIRED';
    public const EXPRESS_MISSING_COUNTRY_CODE = 'EXPRESS_MISSING_COUNTRY_CODE';
    public const EXPRESS_COUNTRY_NOT_FOUND = 'EXPRESS_COUNTRY_NOT_FOUND';

    /**
     * @param class-string<AbstractPaymentHandler> $paymentHandler
     */
    public static function preparedOrderRequired(string $paymentHandler): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::PREPARED_ORDER_REQUIRED,
            'PayPal Order ID does not exist in the request. The payment method {{ paymentHandler }} requires a prepared PayPal order.',
            ['paymentHandler' => $paymentHandler],
        );
    }

    public static function expressMissingCountryCode(): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::EXPRESS_MISSING_COUNTRY_CODE,
            'Missing country code in shipping address',
        );
    }

    public static function expressCountryNotFound(string $countryCode): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::EXPRESS_COUNTRY_NOT_FOUND,
            'Country not found for code: {{ countryCode }}',
            ['countryCode' => $countryCode],
        );
    }
}
