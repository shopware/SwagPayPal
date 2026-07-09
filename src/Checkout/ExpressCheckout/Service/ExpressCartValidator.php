<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\Checkout\ExpressCheckout\Service;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\Checkout\Exception\EmptyCartException;

#[Package('checkout')]
class ExpressCartValidator
{
    public function validateNotEmpty(Cart $cart): void
    {
        if ($cart->getLineItems()->count() === 0) {
            throw new EmptyCartException();
        }
    }
}
