<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Cart\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\PayPal\Checkout\Exception\EmptyCartException;
use Swag\PayPal\Checkout\Exception\OrderZeroValueException;

#[Package('checkout')]
class CartPriceService
{
    public function isProcessable(Cart $cart, SalesChannelContext $context): bool
    {
        return $cart->getLineItems()->count() > 0 && !$this->isZeroValueCart($cart);
    }

    public function validateProcessable(Cart $cart, SalesChannelContext $context): void
    {
        if ($cart->getLineItems()->count() === 0) {
            throw new EmptyCartException();
        }

        if ($this->isZeroValueCart($cart)) {
            throw new OrderZeroValueException();
        }
    }

    public function isZeroValueCart(Cart $cart): bool
    {
        if ($cart->getLineItems()->count() === 0) {
            return false;
        }

        if ($cart->getPrice()->getTotalPrice() > 0) {
            return false;
        }

        return true;
    }
}
