<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Util\Compatibility;

use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Exception\OrderDeliveryNotFoundException;
use Shopware\Core\Checkout\Cart\Exception\OrderNotFoundException;
use Shopware\Core\Checkout\Cart\Exception\OrderTransactionNotFoundException;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Framework\ShopwareHttpException;

/**
 * @internal
 *
 * required until min version 6.4.15.0
 */
class Exception
{
    public static function orderDeliveryNotFound(string $id): ShopwareHttpException
    {
        if (\class_exists(OrderDeliveryNotFoundException::class)) {
            // @phpstan-ignore-next-line
            return new OrderDeliveryNotFoundException($id);
        }

        if (!\class_exists(OrderException::class)) {
            // will never be called, only for phpstan
            throw new \RuntimeException('Missing OrderDeliveryNotFoundException');
        }

        return OrderException::orderDeliveryNotFound($id);
    }

    public static function orderTransactionNotFound(string $id): ShopwareHttpException
    {
        if (\class_exists(OrderTransactionNotFoundException::class)) {
            // @phpstan-ignore-next-line
            return new OrderTransactionNotFoundException($id);
        }

        if (!\class_exists(OrderException::class)) {
            // will never be called, only for phpstan
            throw new \RuntimeException('Missing OrderTransactionNotFoundException');
        }

        return OrderException::orderTransactionNotFound($id);
    }

    public static function orderNotFound(string $id): ShopwareHttpException
    {
        if (\class_exists(OrderNotFoundException::class)) {
            // @phpstan-ignore-next-line
            return new OrderNotFoundException($id);
        }

        if (!\class_exists(OrderException::class)) {
            // will never be called, only for phpstan
            throw new \RuntimeException('Missing OrderNotFoundException');
        }

        return OrderException::orderNotFound($id);
    }

    public static function customerNotLoggedIn(): ShopwareHttpException
    {
        if (\class_exists(CartException::class)) {
            return CartException::customerNotLoggedIn();
        }

        // @phpstan-ignore-next-line
        return new CustomerNotLoggedInException();
    }
}
