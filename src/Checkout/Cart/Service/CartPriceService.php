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
use Swag\PayPal\Util\PriceFormatter;

#[Package('checkout')]
class CartPriceService
{
    /**
     * @internal
     */
    public function __construct(
        private readonly PriceFormatter $priceFormatter,
    ) {
    }

    public function hasZeroPrice(Cart $cart, SalesChannelContext $context): bool
    {
        if ($cart->getLineItems()->count() === 0) {
            return false;
        }

        $price = $this->priceFormatter->roundPrice($cart->getPrice()->getTotalPrice(), $context->getCurrency()->getIsoCode());

        if ($price > 0) {
            return false;
        }

        return true;
    }

    /**
     * @deprecated tag:v11.0.0 - Will be removed and is replaced by {@see self::hasZeroPrice}
     */
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
