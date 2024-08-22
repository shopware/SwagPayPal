<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\Cart\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class CartPriceService
{
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
