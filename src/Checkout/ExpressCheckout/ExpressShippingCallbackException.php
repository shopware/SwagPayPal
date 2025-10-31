<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Checkout\Shipping\Cart\Error\ShippingMethodBlockedError;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\PayPalSDK\Struct\V2\OrderShippingCallback;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

#[Package('checkout')]
class ExpressShippingCallbackException extends HttpException
{
    public const COUNTRY_ERROR = self::PREFIX . 'COUNTRY_ERROR';

    public const ADDRESS_ERROR = self::PREFIX . 'ADDRESS_ERROR';

    public const METHOD_UNAVAILABLE = self::PREFIX . 'METHOD_UNAVAILABLE';
    private const PREFIX = 'EXPRESS_SHIPPING_CALLBACK_';

    public static function countryError(OrderShippingCallback $callback): self
    {
        return new self(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            self::COUNTRY_ERROR,
            'Country error for shipping to "{{ countryCode }}"',
            [
                'orderId' => $callback->getId(),
                'countryCode' => $callback->getShippingAddress()->getCountryCode(),
            ],
        );
    }

    public static function addressError(OrderShippingCallback $callback): self
    {
        return new self(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            self::ADDRESS_ERROR,
            'Address error for shipping to "{{ countryCode }}"',
            [
                'orderId' => $callback->getId(),
                'countryCode' => $callback->getShippingAddress()->getCountryCode(),
            ],
        );
    }

    public static function methodUnavailable(OrderShippingCallback $callback, ShippingMethodBlockedError $error): self
    {
        return new self(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            self::METHOD_UNAVAILABLE,
            'Shipping method "{{ shippingMethodName }}" not available',
            [
                'orderId' => $callback->getId(),
                /** @phpstan-ignore-next-line function.alreadyNarrowedType */
                'shippingMethodId' => \method_exists($error, 'getShippingMethodId') ? $error->getShippingMethodId() : null,
                'shippingMethodName' => $error->getName(),
            ],
        );
    }

    public function intoCallbackResponse(): Response
    {
        return new JsonResponse([
            'name' => 'UNPROCESSABLE_ENTITY',
            'details' => [[
                'issue' => \str_replace(self::PREFIX, '', $this->getErrorCode()),
            ]],
        ], $this->getStatusCode());
    }
}
